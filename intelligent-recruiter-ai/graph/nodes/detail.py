from langchain_core.messages import SystemMessage, AIMessage, ToolMessage
from graph.state import GraphState
from graph.tools.candidates import get_candidate_by_name
from graph.tools.prompts import DETAIL
from graph.llm import get_llm

_TOOLS = [get_candidate_by_name]
_NO_DATA = ["No candidate named", "not found", "Error", "DatabaseError"]


def detail_node(state: GraphState) -> GraphState:
    history = list(state["messages"])
    last = history[-1] if history else None

    if isinstance(last, ToolMessage):
        # Tool already ran — check for errors then get final answer (no tools bound)
        if any(p in (last.content or "") for p in _NO_DATA):
            reply = AIMessage(content="I could not find that candidate in the database. Please check the name or upload their CV first.")
            return {**state, "messages": history + [reply], "intent": "end"}
        response = get_llm(temperature=0).invoke([SystemMessage(content=DETAIL)] + history)
    else:
        # First pass — bind tools so Gemini calls get_candidate_by_name
        response = get_llm(temperature=0).bind_tools(_TOOLS).invoke([SystemMessage(content=DETAIL)] + history)

    return {**state, "messages": history + [response], "intent": None}