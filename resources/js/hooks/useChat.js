import { useState, useEffect, useRef, useCallback } from "react";
import axios from "axios";

export default function useChat(authUserId, initialUnreadCounts) {
    const [messages, setMessages] = useState([]);
    const [unreadCounts, setUnreadCounts] = useState(initialUnreadCounts);
    const [selectedUser, setSelectedUser] = useState(null);
    const [loading, setLoading] = useState(false);
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(false);
    const selectedUserRef = useRef(null);

    useEffect(() => {
        selectedUserRef.current = selectedUser;
    }, [selectedUser]);

    useEffect(() => {
        const channel = window.Echo.private(`messages.${authUserId}`);

        channel.listen("MessageSent", (e) => {
            const current = selectedUserRef.current;

            if (current && e.sender_id === current.id) {
                setMessages((prev) => {
                    if (prev.some((m) => m.id === e.id)) return prev;
                    return [...prev, e];
                });
            } else {
                setUnreadCounts((prev) => ({
                    ...prev,
                    [e.sender_id]: (prev[e.sender_id] || 0) + 1,
                }));
            }
        });

        return () => window.Echo.leave(`messages.${authUserId}`);
    }, [authUserId]);

    const selectUser = async (user) => {
        setSelectedUser(user);
        setLoading(true);
        setPage(1);
        try {
            const { data } = await axios.get(`/messages/${user.id}`, { params: { page: 1 } });
            setMessages(data.data);
            setHasMore(data.meta?.has_more || false);

            setUnreadCounts((prev) => {
                if (!prev[user.id]) return prev;
                const updated = { ...prev };
                delete updated[user.id];
                return updated;
            });
        } finally {
            setLoading(false);
        }
    };

    const loadMore = useCallback(async () => {
        if (!selectedUser || loading || !hasMore) return;

        const nextPage = page + 1;
        setLoading(true);
        try {
            const { data } = await axios.get(`/messages/${selectedUser.id}`, { params: { page: nextPage } });
            setMessages((prev) => [...data.data, ...prev]);
            setHasMore(data.meta?.has_more || false);
            setPage(nextPage);
        } finally {
            setLoading(false);
        }
    }, [selectedUser, loading, hasMore, page]);

    const sendMessage = async (text) => {
        const { data } = await axios.post(`/messages/${selectedUser.id}`, {
            text,
        });
        setMessages((prev) => [...prev, data.data]);
    };

    const clearSelection = () => {
        setSelectedUser(null);
        setMessages([]);
    };

    return {
        messages,
        unreadCounts,
        selectedUser,
        selectUser,
        sendMessage,
        loading,
        loadMore,
        hasMore,
        clearSelection,
    };
}
