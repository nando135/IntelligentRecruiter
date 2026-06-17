from langchain_core.messages import SystemMessage, AIMessage
from graph.state import GraphState
from graph.tools.prompts import CHAT
from graph.llm import get_llm


def _llm():
    return get_llm(temperature=0.7)


def chat_node(state: GraphState) -> GraphState:
    history = list(state["messages"])
    response = _llm().invoke([SystemMessage(content=CHAT)] + history)
    return {**state, "messages": history + [AIMessage(content=response.content)], "intent": None}