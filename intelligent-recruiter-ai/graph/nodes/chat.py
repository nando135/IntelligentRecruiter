import os
from langchain_core.messages import SystemMessage, AIMessage
from langchain_ollama import ChatOllama
from graph.state import GraphState
from graph.tools.prompts import CHAT


def _llm():
    return ChatOllama(
        model=os.getenv("OLLAMA_MODEL", "llama3.2:1b"),
        base_url=os.getenv("OLLAMA_URL", "http://127.0.0.1:11434"),
        temperature=0.7,
    )


def chat_node(state: GraphState) -> GraphState:
    history = list(state["messages"])
    response = _llm().invoke([SystemMessage(content=CHAT)] + history)
    return {**state, "messages": history + [AIMessage(content=response.content)], "intent": None}