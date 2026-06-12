import os
import json
from langchain_core.messages import SystemMessage, AIMessage, ToolMessage, HumanMessage
from langchain_ollama import ChatOllama
from langgraph.prebuilt import ToolNode
from graph.state import GraphState
from graph.tools.candidates import get_candidates_summary
from graph.tools.prompts import COMPARE

_TOOLS = [get_candidates_summary]
_NO_DATA = ["No candidates found", "Error", "DatabaseError"]


def _llm(force_tool=False):
    llm = ChatOllama(
        model=os.getenv("OLLAMA_MODEL", "llama3.2:1b"),
        base_url=os.getenv("OLLAMA_URL", "http://127.0.0.1:11434"),
        temperature=0.3,
    )
    if force_tool:
        return llm.bind_tools(_TOOLS, tool_choice="get_candidates_summary")
    return llm


compare_tool_node = ToolNode(_TOOLS)


def _extract_user_text(messages: list) -> str:
    for m in reversed(messages):
        if isinstance(m, HumanMessage):
            if isinstance(m.content, str):
                return m.content.lower()
            return " ".join(
                p.get("text", "") if isinstance(p, dict) else str(p)
                for p in m.content
            ).lower()
    return ""


def _filter_candidates(raw_json: str, user_text: str) -> str:
    """
    Filter the candidates list to only those whose name appears in the user message.
    If no names match, return all candidates so the model can respond correctly.
    """
    try:
        candidates = json.loads(raw_json)
    except Exception:
        return raw_json

    if not isinstance(candidates, list):
        return raw_json

    matched = [
        c for c in candidates
        if c.get("full_name") and c["full_name"].lower() in user_text
    ]

    # Also try partial first-name match
    if not matched:
        matched = [
            c for c in candidates
            if c.get("full_name") and any(
                part in user_text
                for part in c["full_name"].lower().split()
                if len(part) > 2  # skip short words
            )
        ]

    return json.dumps(matched if matched else candidates, default=str)


def compare_node(state: GraphState) -> GraphState:
    history = list(state["messages"])
    last = history[-1] if history else None

    if isinstance(last, ToolMessage):
        if any(p in (last.content or "") for p in _NO_DATA):
            reply = AIMessage(content="There are no candidates in the database yet. Please upload some CVs first.")
            return {**state, "messages": history + [reply], "intent": "end"}
        response = _llm().invoke([SystemMessage(content=COMPARE)] + history)
        return {**state, "messages": history + [response], "intent": None}

    # Call tool directly then filter to only requested candidates
    tool_result = get_candidates_summary.invoke({})

    if any(p in (tool_result or "") for p in _NO_DATA):
        tool_msg = ToolMessage(content=tool_result, tool_call_id="direct_call", name="get_candidates_summary")
        reply = AIMessage(content="There are no candidates in the database yet. Please upload some CVs first.")
        return {**state, "messages": history + [tool_msg, reply], "intent": "end"}

    user_text = _extract_user_text(history)
    filtered = _filter_candidates(tool_result, user_text)

    tool_msg = ToolMessage(
        content=filtered,
        tool_call_id="direct_call",
        name="get_candidates_summary",
    )

    response = _llm().invoke([SystemMessage(content=COMPARE)] + history + [tool_msg])
    return {**state, "messages": history + [tool_msg, response], "intent": None}