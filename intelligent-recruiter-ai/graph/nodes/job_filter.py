from langchain_core.messages import SystemMessage, AIMessage, ToolMessage
from graph.state import GraphState
from graph.tools.candidates import get_candidates_summary
from graph.tools.prompts import JOB_FILTER
from graph.llm import get_llm

_TOOLS = [get_candidates_summary]
_NO_DATA = ["No candidates found", "Error", "DatabaseError"]


def job_filter_node(state: GraphState) -> GraphState:
    history = list(state["messages"])
    last = history[-1] if history else None

    if isinstance(last, ToolMessage):
        # Tool already ran — check for errors then get final answer (no tools bound)
        if any(p in (last.content or "") for p in _NO_DATA):
            reply = AIMessage(content="There are no candidates in the database yet. Please upload some CVs first.")
            return {**state, "messages": history + [reply], "intent": "end"}
        response = get_llm(temperature=0.3).invoke([SystemMessage(content=JOB_FILTER)] + history)
    else:
        # First pass — bind tools so Gemini calls get_candidates_summary
        response = get_llm(temperature=0.3).bind_tools(_TOOLS).invoke([SystemMessage(content=JOB_FILTER)] + history)

    return {**state, "messages": history + [response], "intent": None}