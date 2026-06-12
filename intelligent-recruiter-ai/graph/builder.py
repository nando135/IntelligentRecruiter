import os
from langchain_core.messages import AIMessage, HumanMessage, SystemMessage
from langchain_ollama import ChatOllama
from langgraph.graph import END, StateGraph
from langgraph.checkpoint.base import BaseCheckpointSaver

from graph.state import GraphState
from graph.tools.prompts import OUT_OF_SCOPE
from graph.nodes import (
    router_node, route_edge,
    compare_node, compare_tool_node,
    detail_node, detail_tool_node,
    job_filter_node, job_filter_tool_node,
    chat_node,
)


def _has_tool_call(state: GraphState) -> str:
    last = state["messages"][-1] if state["messages"] else None
    if isinstance(last, AIMessage) and getattr(last, "tool_calls", None):
        return "run_tool"
    if state.get("intent") == "end":
        return "go_end"
    return "done"


def _end_node(state: GraphState) -> GraphState:
    msgs = list(state["messages"]) + [AIMessage(content=OUT_OF_SCOPE)]
    return {**state, "messages": msgs, "intent": None, "turn_count": 0}


def build_graph(checkpointer: BaseCheckpointSaver | None = None):
    g = StateGraph(GraphState)

    g.add_node("router",           router_node)
    g.add_node("end_node",         _end_node)
    g.add_node("chat_node",        chat_node)
    g.add_node("compare",          compare_node)
    g.add_node("compare_tools",    compare_tool_node)
    g.add_node("detail",           detail_node)
    g.add_node("detail_tools",     detail_tool_node)
    g.add_node("job_filter",       job_filter_node)
    g.add_node("job_filter_tools", job_filter_tool_node)

    g.set_entry_point("router")

    g.add_conditional_edges("router", route_edge, {
        "compare":    "compare",
        "detail":     "detail",
        "job_filter": "job_filter",
        "chat_node":  "chat_node",
        "end_node":   "end_node",
    })

    for node, tool_node in [
        ("compare",    "compare_tools"),
        ("detail",     "detail_tools"),
        ("job_filter", "job_filter_tools"),
    ]:
        g.add_conditional_edges(node, _has_tool_call, {
            "run_tool": tool_node,
            "go_end":   "end_node",
            "done":     END,
        })
        g.add_edge(tool_node, node)

    g.add_edge("chat_node", END)
    g.add_edge("end_node",  END)

    return g.compile(checkpointer=checkpointer)