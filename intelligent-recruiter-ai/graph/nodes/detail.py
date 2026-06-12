import os
import re
from langchain_core.messages import SystemMessage, AIMessage, ToolMessage, HumanMessage
from langchain_ollama import ChatOllama
from langgraph.prebuilt import ToolNode
from graph.state import GraphState
from graph.tools.candidates import get_candidate_by_name
from graph.tools.prompts import DETAIL

_TOOLS = [get_candidate_by_name]
_NO_DATA = ["No candidate named", "not found", "Error", "DatabaseError"]

# Phrases to strip from the user message to isolate the candidate name
_STRIP_PHRASES = [
    "can you tell me about", "tell me more about", "tell me about",
    "what do you know about", "what can you tell me about",
    "give me details about", "give me information about", "give me info on",
    "details about", "details on", "information on", "info on",
    "more about", "who exactly is", "i want to know about",
    "i'd like to know about", "about candidate", "find candidate",
    "look up", "lookup", "profile of", "background of",
    "show me", "who is", "describe", "explain",
]


def _llm(force_tool=False):
    llm = ChatOllama(
        model=os.getenv("OLLAMA_MODEL", "llama3.2:1b"),
        base_url=os.getenv("OLLAMA_URL", "http://127.0.0.1:11434"),
        temperature=0,
    )
    if force_tool:
        return llm.bind_tools(_TOOLS, tool_choice="get_candidate_by_name")
    return llm


detail_tool_node = ToolNode(_TOOLS)


def _extract_name(messages: list) -> str:
    """Strip intent phrases from the user message to isolate the candidate name."""
    for m in reversed(messages):
        if isinstance(m, HumanMessage):
            text = m.content if isinstance(m.content, str) else " ".join(
                p.get("text", "") if isinstance(p, dict) else str(p)
                for p in m.content
            )
            lower = text.lower().strip()
            # Remove known intent phrases
            for phrase in sorted(_STRIP_PHRASES, key=len, reverse=True):
                if lower.startswith(phrase):
                    lower = lower[len(phrase):].strip()
                    break
            # Remove trailing punctuation
            name = re.sub(r"[?.!,]+$", "", lower).strip()
            return name
    return ""


def detail_node(state: GraphState) -> GraphState:
    history = list(state["messages"])
    last = history[-1] if history else None

    if isinstance(last, ToolMessage):
        if any(p in (last.content or "") for p in _NO_DATA):
            reply = AIMessage(content="I could not find that candidate in the database. Please check the name or upload their CV first.")
            return {**state, "messages": history + [reply], "intent": "end"}
        response = _llm().invoke([SystemMessage(content=DETAIL)] + history)
        return {**state, "messages": history + [response], "intent": None}

    # Extract clean candidate name then call tool directly
    name = _extract_name(history)
    tool_result = get_candidate_by_name.invoke({"name": name})
    tool_msg = ToolMessage(
        content=tool_result,
        tool_call_id="direct_call",
        name="get_candidate_by_name",
    )

    if any(p in (tool_result or "") for p in _NO_DATA):
        reply = AIMessage(content="I could not find that candidate in the database. Please check the name or upload their CV first.")
        return {**state, "messages": history + [tool_msg, reply], "intent": "end"}

    response = _llm().invoke([SystemMessage(content=DETAIL)] + history + [tool_msg])
    return {**state, "messages": history + [tool_msg, response], "intent": None}