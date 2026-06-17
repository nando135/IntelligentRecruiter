from __future__ import annotations

from langchain_core.messages import AIMessage, BaseMessage, HumanMessage, SystemMessage

from graph.builder import build_graph
from graph.state import GraphState

_graph = build_graph(checkpointer=None)
_checkpointer = None


def _get_checkpointer():
    global _checkpointer
    if _checkpointer is None:
        from graph.checkpointer import MySQLCheckpointer
        _checkpointer = MySQLCheckpointer()
    return _checkpointer


def run_chat(thread_id: str, user_message: str) -> str:
    cp = _get_checkpointer()
    saved = cp.load(thread_id)
    messages = _deserialize(saved["messages"]) if saved else []
    messages.append(HumanMessage(content=user_message))

    state: GraphState = {"messages": messages, "intent": None}
    result = _graph.invoke(state)

    cp.save(thread_id, {
        "messages": _serialize(result["messages"]),
        "intent": None,
    })

    ai_msgs = [m for m in result["messages"] if isinstance(m, AIMessage)]
    if not ai_msgs:
        return "No response generated."
    return _content_str(ai_msgs[-1].content)


def clear_thread(thread_id: str):
    _get_checkpointer().delete(thread_id)


def _content_str(content) -> str:
    """Normalize Gemini content — newer models return a list of parts."""
    if isinstance(content, list):
        return " ".join(
            part.get("text", "") if isinstance(part, dict) else str(part)
            for part in content
        ).strip()
    return str(content or "").strip()


def _serialize(messages: list[BaseMessage]) -> list[dict]:
    role_map = {HumanMessage: "human", AIMessage: "ai", SystemMessage: "system"}
    return [
        {"role": role_map.get(type(m), "system"), "content": _content_str(m.content)}
        for m in messages
        if type(m) in role_map
    ]


def _deserialize(data: list[dict]) -> list[BaseMessage]:
    cls_map = {"human": HumanMessage, "ai": AIMessage, "system": SystemMessage}
    return [
        cls_map[d["role"]](content=d["content"])
        for d in data
        if d.get("role") in cls_map
    ]