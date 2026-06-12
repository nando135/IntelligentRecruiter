import os
from langchain_core.messages import SystemMessage, AIMessage, ToolMessage
from langchain_ollama import ChatOllama
from langgraph.prebuilt import ToolNode
from graph.state import GraphState
from graph.tools.candidates import get_candidates_summary
from graph.tools.prompts import JOB_FILTER

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


job_filter_tool_node = ToolNode(_TOOLS)


def job_filter_node(state: GraphState) -> GraphState:
    history = list(state["messages"])
    last = history[-1] if history else None

    if isinstance(last, ToolMessage):
        if any(p in (last.content or "") for p in _NO_DATA):
            reply = AIMessage(content="There are no candidates in the database yet. Please upload some CVs first.")
            return {**state, "messages": history + [reply], "intent": "end"}
        response = _llm().invoke([SystemMessage(content=JOB_FILTER)] + history)
        return {**state, "messages": history + [response], "intent": None}

    # Call tool directly — llama3.2:1b cannot reliably honor bind_tools
    tool_result = get_candidates_summary.invoke({})
    tool_msg = ToolMessage(
        content=tool_result,
        tool_call_id="direct_call",
        name="get_candidates_summary",
    )

    if any(p in (tool_result or "") for p in _NO_DATA):
        reply = AIMessage(content="There are no candidates in the database yet. Please upload some CVs first.")
        return {**state, "messages": history + [tool_msg, reply], "intent": "end"}

    response = _llm().invoke([SystemMessage(content=JOB_FILTER)] + history + [tool_msg])
    return {**state, "messages": history + [tool_msg, response], "intent": None}