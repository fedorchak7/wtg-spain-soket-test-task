import { useState, useEffect } from "react";

export default function useOnlineUsers() {
    const [onlineUsers, setOnlineUsers] = useState([]);

    useEffect(() => {
        window.Echo.join("online")
            .here((users) => setOnlineUsers(users))
            .joining((user) => setOnlineUsers((prev) => [...prev, user]))
            .leaving((user) =>
                setOnlineUsers((prev) => prev.filter((u) => u.id !== user.id)),
            );

        return () => window.Echo.leave("online");
    }, []);

    return onlineUsers;
}
