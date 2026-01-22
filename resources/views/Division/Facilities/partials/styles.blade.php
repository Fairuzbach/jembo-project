        <style>
            /* Enhanced Input Styling */
            input[type="text"],
            input[type="email"],
            input[type="password"],
            input[type="number"],
            input[type="date"],
            input[type="month"],
            input[type="time"],
            textarea,
            select {
                @apply transition-all duration-200 shadow-sm;
            }

            input[type="text"]:focus,
            input[type="email"]:focus,
            input[type="password"]:focus,
            input[type="number"]:focus,
            input[type="date"]:focus,
            input[type="month"]:focus,
            input[type="time"]:focus,
            textarea:focus,
            select:focus {
                @apply shadow-md outline-none;
            }

            /* Button Global Styles */
            button {
                @apply transition-all duration-200 font-medium;
            }

            button:not(.no-scale):hover {
                @apply -translate-y-0.5;
            }

            button:not(.no-scale):active {
                @apply translate-y-0;
            }

            /* Smooth transitions for modals */
            [x-cloak] {
                @apply hidden;
            }

            /* Table row hover effect */
            tbody tr {
                @apply transition-colors duration-150;
            }

            tbody tr:hover {
                @apply bg-gradient-to-r from-blue-50/50 to-transparent;
            }

            /* Smooth scrollbar for modals */
            .custom-scrollbar::-webkit-scrollbar {
                width: 6px;
            }

            .custom-scrollbar::-webkit-scrollbar-track {
                background: rgba(226, 232, 240, 0.5);
                border-radius: 3px;
            }

            .custom-scrollbar::-webkit-scrollbar-thumb {
                background: rgba(148, 163, 184, 0.6);
                border-radius: 3px;
            }

            .custom-scrollbar::-webkit-scrollbar-thumb:hover {
                background: rgba(148, 163, 184, 0.9);
            }

            /* Pulse animation for active filters */
            @keyframes pulse-soft {

                0%,
                100% {
                    opacity: 1;
                }

                50% {
                    opacity: 0.7;
                }
            }

            .animate-pulse {
                animation: pulse-soft 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            }

            /* Fade in animation */
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(-4px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .animate-fadeIn {
                animation: fadeIn 0.2s ease-out;
            }

            .custom-scrollbar::-webkit-scrollbar {
                width: 6px;
            }

            .custom-scrollbar::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 10px;
            }
        </style>
