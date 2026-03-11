import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, usePage } from "@inertiajs/react";
import { useMemo } from "react";
import UserList from "@/Components/UserList";
import ChatBox from "@/Components/ChatBox";
import useOnlineUsers from "@/hooks/useOnlineUsers";
import useChat from "@/hooks/useChat";

export default function Dashboard() {
    const { users, authUser } = usePage().props;

    const initialCounts = useMemo(() => {
        const counts = {};
        users.data.forEach((u) => {
            if (u.unread_count > 0) counts[u.id] = u.unread_count;
        });
        return counts;
    }, [users.data]);

    const onlineUsers = useOnlineUsers();
    const otherOnlineUsers = useMemo(
        () => onlineUsers.filter((u) => u.id !== authUser.data.id),
        [onlineUsers, authUser.data.id],
    );
    const {
        messages,
        unreadCounts,
        selectedUser,
        selectUser,
        sendMessage,
        loading,
        loadMore,
        hasMore,
        clearSelection,
    } = useChat(authUser.data.id, initialCounts);

    return (
        <AuthenticatedLayout>
            <Head title="Chat" />
            <div className="flex h-[calc(100vh-65px)]">
                <UserList
                    users={users.data}
                    selectedUser={selectedUser}
                    onlineUsers={otherOnlineUsers}
                    unreadCounts={unreadCounts}
                    onSelect={selectUser}
                />
                <ChatBox
                    messages={messages}
                    selectedUser={selectedUser}
                    authUser={authUser.data}
                    onSend={sendMessage}
                    loading={loading}
                    onLoadMore={loadMore}
                    hasMore={hasMore}
                    onBack={clearSelection}
                />
            </div>
        </AuthenticatedLayout>
    );
}
