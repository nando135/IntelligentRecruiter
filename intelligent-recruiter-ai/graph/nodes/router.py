import os
from langchain_core.messages import SystemMessage, HumanMessage, AIMessage
from langchain_ollama import ChatOllama
from graph.state import GraphState
from graph.tools.prompts import ROUTER

_VALID = {"compare", "candidate_detail", "job_filter", "chat", "end"}

_CHAT_KEYWORDS = {
    "hello", "hi", "hey", "good morning", "good afternoon", "good evening",
    "thank you", "thanks", "thank", "thx", "ty",
    "bye", "goodbye", "see you", "take care",
    "ok", "okay", "alright", "great", "cool", "nice", "good",
    "yes", "no", "sure", "noted", "got it",
}
_COMPARE_KEYWORDS = {
    "compare", "vs", "versus", "difference between", "better between",
    "which is better", "who is better", "contrast",
}
_DETAIL_KEYWORDS = {
    "tell me about", "tell me more about", "can you tell me about",
    "who is", "show me", "profile of", "background of",
    "give me info on", "give me information about", "give me details about",
    "more about", "explain", "describe", "what do you know about",
    "what can you tell me about", "info on", "information on",
    "details on", "details about", "about candidate", "find candidate",
    "look up", "lookup", "who exactly is", "i want to know about",
    "i'd like to know about",
}
_JOB_KEYWORDS = {
    "best for", "fit for", "suitable for", "who fits", "who can",
    "role", "position", "job", "hire", "hiring",
    "recommend for", "who should i hire", "who would be good for",
    "which candidate", "which is the best", "who is the best",
    "best candidate", "top candidate", "most qualified", "who qualifies",
    "who has experience in", "who knows", "who is good at",
}


def _llm():
    return ChatOllama(
        model=os.getenv("OLLAMA_MODEL", "llama3.2:1b"),
        base_url=os.getenv("OLLAMA_URL", "http://127.0.0.1:11434"),
        temperature=0,
    )


def _extract_text(content) -> str:
    if isinstance(content, list):
        return " ".join(
            p.get("text", "") if isinstance(p, dict) else str(p)
            for p in content
        )
    return str(content or "")


def _keyword_classify(text: str) -> str | None:
    lower = text.lower().strip()

    if lower in _CHAT_KEYWORDS or any(lower.startswith(k + " ") or lower == k for k in _CHAT_KEYWORDS):
        return "chat"

    if any(k in lower for k in _COMPARE_KEYWORDS):
        return "compare"

    if any(k in lower for k in _DETAIL_KEYWORDS):
        return "candidate_detail"

    if any(k in lower for k in _JOB_KEYWORDS):
        return "job_filter"

    return None


def router_node(state: GraphState) -> GraphState:
    messages = list(state["messages"])

    last_human = next(
        (m for m in reversed(messages) if isinstance(m, HumanMessage)),
        None,
    )
    last = _extract_text(last_human.content) if last_human else ""

    intent = _keyword_classify(last)

    if intent is None:
        response = _llm().invoke([
            SystemMessage(content=ROUTER),
            HumanMessage(content=last),
        ])
        raw = _extract_text(response.content).strip().lower().split()[0] if response.content else "end"
        intent = raw if raw in _VALID else "end"

    return {**state, "intent": intent, "turn_count": 0}


def route_edge(state: GraphState) -> str:
    mapping = {
        "compare":          "compare",
        "candidate_detail": "detail",
        "job_filter":       "job_filter",
        "chat":             "chat_node",
        "end":              "end_node",
    }
    return mapping.get(state.get("intent") or "end", "end_node")