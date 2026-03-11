export default function MessageBubble({ message, isMine }) {
    const formatTime = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleTimeString([], {
            hour: "2-digit",
            minute: "2-digit",
        });
    };

    return (
        <div className={`flex ${isMine ? "justify-end" : "justify-start"}`}>
            <div
                className={`max-w-xs px-4 py-2 rounded-2xl ${
                    isMine
                        ? "bg-indigo-500 text-white"
                        : "bg-white text-gray-900 border"
                }`}
            >
                <div>{message.text}</div>
                <div
                    className={`text-xs mt-1 ${isMine ? "text-indigo-200" : "text-gray-400"}`}
                >
                    {formatTime(message.created_at)}
                </div>
            </div>
        </div>
    );
}
