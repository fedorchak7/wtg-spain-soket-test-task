export default function UserList({
    users,
    selectedUser,
    onlineUsers,
    unreadCounts,
    onSelect,
}) {
    const isOnline = (userId) => onlineUsers.some((u) => u.id === userId);

    return (
        <div
            className={`w-full md:w-72 border-r bg-white overflow-y-auto ${
                selectedUser ? "hidden md:block" : "block"
            }`}
        >
            <div className="p-4 border-b font-semibold text-gray-700">
                Users{" "}
                <span className="text-sm font-normal text-gray-400">
                    ({onlineUsers.length} online)
                </span>
            </div>
            {users.map((user) => (
                <button
                    key={user.id}
                    onClick={() => onSelect(user)}
                    className={`w-full text-left px-4 py-3 flex items-center justify-between hover:bg-gray-50 transition ${
                        selectedUser?.id === user.id
                            ? "bg-indigo-50 border-r-2 border-indigo-500"
                            : ""
                    }`}
                >
                    <div className="flex items-center gap-2">
                        <span
                            className={`w-2.5 h-2.5 rounded-full flex-shrink-0 ${
                                isOnline(user.id)
                                    ? "bg-green-500"
                                    : "bg-gray-300"
                            }`}
                        />
                        <div className="min-w-0">
                            <div className="font-medium text-gray-900 truncate">
                                {user.name}
                            </div>
                            <div className="text-sm text-gray-500 truncate">
                                {user.email}
                            </div>
                        </div>
                    </div>
                    {unreadCounts[user.id] > 0 && (
                        <span className="bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0">
                            {unreadCounts[user.id]}
                        </span>
                    )}
                </button>
            ))}
        </div>
    );
}
