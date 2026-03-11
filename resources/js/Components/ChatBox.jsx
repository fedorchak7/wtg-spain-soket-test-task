import { useState, useEffect, useRef } from "react";
import MessageBubble from "@/Components/MessageBubble";
import Spinner from "@/Components/Spinner";

export default function ChatBox({
    messages,
    selectedUser,
    authUser,
    onSend,
    loading,
    onLoadMore,
    hasMore,
    onBack,
}) {
    const [text, setText] = useState("");
    const [sending, setSending] = useState(false);
    const [sendError, setSendError] = useState(null);
    const [loadingMore, setLoadingMore] = useState(false);
    const bottomRef = useRef(null);
    const scrollRef = useRef(null);
    const prevMessagesLength = useRef(0);
    const isFirstLoad = useRef(true);
    const loadingMoreRef = useRef(false);

    useEffect(() => {
        if (messages.length === 0) return;

        if (isFirstLoad.current) {
            bottomRef.current?.scrollIntoView();
            isFirstLoad.current = false;
        } else if (messages.length > prevMessagesLength.current) {
            const diff = messages.length - prevMessagesLength.current;
            if (diff === 1) {
                bottomRef.current?.scrollIntoView({ behavior: "smooth" });
            }
        }

        prevMessagesLength.current = messages.length;
    }, [messages]);

    useEffect(() => {
        isFirstLoad.current = true;
        prevMessagesLength.current = 0;
    }, [selectedUser?.id]);

    const handleScroll = () => {
        const el = scrollRef.current;
        if (!el || !hasMore || loading || loadingMoreRef.current) return;

        if (el.scrollTop < 150) {
            loadingMoreRef.current = true;
            setLoadingMore(true);
            const prevHeight = el.scrollHeight;

            onLoadMore().then(() => {
                requestAnimationFrame(() => {
                    if (scrollRef.current) {
                        scrollRef.current.scrollTop =
                            scrollRef.current.scrollHeight - prevHeight;
                    }
                    setLoadingMore(false);
                    loadingMoreRef.current = false;
                });
            });
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!text.trim() || sending) return;
        setSending(true);
        setSendError(null);
        try {
            await onSend(text.trim());
            setText("");
        } catch {
            setSendError("Не удалось отправить сообщение. Попробуйте ещё раз.");
        } finally {
            setSending(false);
        }
    };

    if (!selectedUser) {
        return (
            <div className="hidden md:flex flex-1 items-center justify-center text-gray-400">
                Select a user to start chatting
            </div>
        );
    }

    return (
        <div
            className={`flex-1 flex flex-col ${
                selectedUser ? "block" : "hidden md:block"
            }`}
        >
            <div className="p-4 border-b bg-white font-semibold text-gray-700 flex items-center gap-3">
                <button
                    onClick={() => onBack()}
                    className="md:hidden text-gray-500 hover:text-gray-700"
                >
                    ←
                </button>
                {selectedUser.name}
            </div>

            <div
                ref={scrollRef}
                onScroll={handleScroll}
                className="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50"
            >
                {loading && messages.length === 0 ? (
                    <div className="flex items-center justify-center h-full text-gray-400">
                        <Spinner size="md" />
                        <span className="ml-2">Loading messages...</span>
                    </div>
                ) : messages.length === 0 ? (
                    <div className="flex items-center justify-center h-full text-gray-400">
                        No messages yet. Say hi!
                    </div>
                ) : (
                    <>
                        {loadingMore && (
                            <div className="flex items-center justify-center py-3">
                                <Spinner size="sm" />
                                <span className="ml-2 text-sm text-gray-400">
                                    Loading older messages...
                                </span>
                            </div>
                        )}

                        {messages.map((msg) => (
                            <MessageBubble
                                key={msg.id}
                                message={msg}
                                isMine={msg.sender_id === authUser.id}
                            />
                        ))}
                    </>
                )}
                <div ref={bottomRef} />
            </div>

            <div className="p-4 bg-white border-t">
                {sendError && (
                    <p className="mb-2 text-sm text-red-500">{sendError}</p>
                )}
                <form
                    onSubmit={handleSubmit}
                    className="flex gap-2"
                    autoComplete="off"
                >
                    <input
                        type="text"
                        value={text}
                        onChange={(e) => setText(e.target.value)}
                        maxLength={5000}
                        placeholder="Type a message..."
                        className="flex-1 border border-gray-300 rounded-full px-4 py-2 focus:outline-none focus:border-indigo-500"
                    />
                    <button
                        type="submit"
                        disabled={sending || !text.trim()}
                        className="bg-indigo-500 text-white px-6 py-2 rounded-full hover:bg-indigo-600 transition disabled:opacity-50"
                    >
                        {sending ? "..." : "Send"}
                    </button>
                </form>
            </div>
        </div>
    );
}
