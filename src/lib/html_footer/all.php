<?php
require_once "bootstrap.php";
require_once "toastr.php";
require_once "sortable.php";
?>


<script>
    function renderCallLog(transcript) {
        let script = `
        <style>
            .chat-bubble {
                border-radius: 15px;
                padding: 8px 12px;
                margin-bottom: 8px;
                max-width: 70%;
                position: relative; /* Required for timestamp positioning */
            }
            .sender-bubble {
                display: inline-block; /* Makes the bubble only as wide as its content */
                background-color: #007bff;
                color: white;
                text-align: right;
                margin-left: auto;
            }
            .recipient-bubble {
                background-color: #f1f1f1;
                color: #333;
                text-align: left;
            }
            .chat-box {
                max-height: 400px;
                overflow-y: auto;
                padding: 15px;
            }
            .chat-avatar {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                margin-right: 8px;
            }
            .chat-container {
                background-color: #f9f9f9;
                display: flex;
                flex-direction: column;
            }
            .timestamp {
                font-size: 12px;
                margin-top: 4px;
            }
            .sender-timestamp {
                color: white;
                text-align: right;
            }
            .recipient-timestamp {
                color: #333;
                text-align: left;
            }
            .system-notification {
                text-align: center;
                font-size: 14px;
                color: #888;
                margin: 10px 0;
            }
            .system-notification-timestamp {
                font-size: 12px;
                color: #888;
                margin-top: 4px;
                text-align: center;
            }
        </style>
    `;
        script += `<div class="container chat-container border"><div class="chat-box">`;

        let lastSpeaker = null;
        let currentMessage = "";
        let groupedMessages = [];

        // Helper function to convert epoch to readable time
        function convertEpochToTime(epoch) {
            if (!epoch) {
                return ""; // Return an empty string if the epoch is null or undefined
            }

            const date = new Date(epoch * 1000); // Convert seconds to milliseconds
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }); // Format as "10:00 AM"
        }

        transcript.forEach(line => {
            if (line.transcriptText !== "") {
                if (line.speaker === "agent-action") {
                    // Finalize the current ongoing message
                    if (currentMessage) {
                        groupedMessages.push({
                            isSender: lastSpeaker,
                            isSystemNotification: false,
                            message: currentMessage,
                            timestamp: line.timestamp
                        });
                        currentMessage = ""; // Clear the current message
                    }

                    // Add system notification
                    groupedMessages.push({
                        isSystemNotification: true,
                        message: line.transcriptText,
                        timestamp: line.timestamp
                    });
                } else {
                    let isSender = line.speaker === "assistant";

                    if (lastSpeaker === null || lastSpeaker === isSender) {
                        currentMessage += (currentMessage ? " " : "") + line.transcriptText;
                    } else {
                        // Finalize the current speaker's message
                        groupedMessages.push({
                            isSender: lastSpeaker,
                            isSystemNotification: false,
                            message: currentMessage,
                            timestamp: line.timestamp
                        });
                        currentMessage = line.transcriptText; // Start new message for the current speaker
                    }
                    lastSpeaker = isSender;
                }
            }
        });

        // Add the last grouped message
        if (currentMessage) {
            groupedMessages.push({
                isSender: lastSpeaker,
                isSystemNotification: false,
                message: currentMessage,
                timestamp: transcript[transcript.length - 1].timestamp
            });
        }

        // Build the chat messages with timestamps
        groupedMessages.forEach(group => {
            const readableTime = convertEpochToTime(group.timestamp);
            let displayedTime = "";

            if (group.isSystemNotification) {
                // Render system notification below the previous chat bubble
                script += `
                <div class="system-notification">
                    <em>${group.message} at ${readableTime}</em>
                </div>
                `;
            } else {
                if (group.isSender) {
                    if (readableTime) {
                        displayedTime = `<div class='timestamp sender-timestamp'>${readableTime}</div>`
                    }
                    script += `
                    <div class="d-flex justify-content-end mb-3">
                        <div class="chat-bubble sender-bubble">
                            ${group.message}
                        ` + displayedTime + `
                        </div>
                        <img src="/dev/images/ai-model/tess.jpg" alt="avatar" class="chat-avatar">
                    </div>
                `;
                } else {
                    if (readableTime) {
                        displayedTime = `<div class='timestamp recipient-timestamp'>${readableTime}</div>`
                    }
                    script += `
                    <div class="d-flex mb-3">
                        <img src="/dev/images/ai-model/placeholder.png" alt="avatar" class="chat-avatar">
                        <div class="chat-bubble recipient-bubble">
                            ${group.message}
                        ` + displayedTime + `
                        </div>
                    </div>
                `;
                }
            }
        });

        script += `</div></div>`;
        return script;
    }
</script>
