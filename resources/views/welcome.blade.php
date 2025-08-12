<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>ct-backend-service</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                background: #f0f8ff;
                font-family: 'Segoe UI', sans-serif;
                display: flex;
                text-align: center;
                align-items: center;
                justify-content: center;
                justify-items: center;
                height: 100vh;
                color: #1a3f74;
            }

            .container {
                background-color: #ffffff;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 10px 20px rgba(0,0,0,0.1);
                width: 80rem;
                height: 30rem;
                display: flex;
                flex-direction: column;
                justify-items: center;
                justify-content: center;
                align-items: center;
            }

            .status {
                font-size: 2rem;
                margin-bottom: 15px;
                color: #2a5dba;
                text-transform: uppercase;
            }

            .message {
                font-size: 1.2rem;
                margin-bottom: 30px;
                color: #FF04CB;
            }

            .app-logo {
                background: #a0aec0;
                padding: 1rem;
                border-radius: 50%;
                margin-bottom: 2rem;
            }

            .app-logo img {
                width: 120px;
                height: auto;
            }
            .pulse {
                display: inline-block;
                width: 14px;
                height: 14px;
                background-color: #2ecc71;
                border-radius: 50%;
                animation: pulse 1.2s infinite;
                margin-right: 10px;
            }

            @keyframes pulse {
                0% {
                    transform: scale(0.9);
                    opacity: 0.7;
                }
                50% {
                    transform: scale(1.2);
                    opacity: 1;
                }
                100% {
                    transform: scale(0.9);
                    opacity: 0.7;
                }
            }
        </style>
    </head>
    <body>
    <div class="container">
        <div class="app-logo"><img src="{{ asset('images/logo.png') }}" alt="Logo"></div>
        <div class="status">
            <span class="pulse"></span> Application is Running
        </div>
        <div class="message">
            Huuraaay! Application is up and running. <br>
            Everything looks good 🤓👍🏽.
        </div>
    </div>
    </body>
</html>
