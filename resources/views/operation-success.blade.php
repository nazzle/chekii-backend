<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operation Successful</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
        }

        .success-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
            text-align: center;
            animation: fadeIn 0.5s ease-out;
        }

        .success-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            padding: 40px 20px;
            color: white;
            position: relative;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            animation: bounceIn 0.8s ease-out;
        }

        .success-icon:before {
            content: "✓";
            font-size: 40px;
            font-weight: bold;
            color: #4facfe;
        }

        .success-icon:after {
            content: "";
            position: absolute;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.3);
            top: -10px;
            left: -10px;
            animation: pulse 2s infinite;
        }

        h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .message {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.5;
        }

        .success-body {
            padding: 40px 30px;
        }

        .details {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
            border-left: 4px solid #4facfe;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
        }

        .detail-value {
            color: #333;
            font-weight: 500;
        }

        .options-title {
            text-align: left;
            margin-bottom: 20px;
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }

        .buttons-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 15px 20px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(79, 172, 254, 0.4);
        }

        .btn-secondary {
            background: #f0f4f8;
            color: #4a5568;
            border: 1px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            transform: translateY(-3px);
        }

        .btn-success {
            background: linear-gradient(135deg, #38b2ac 0%, #319795 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(56, 178, 172, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(56, 178, 172, 0.4);
        }

        .btn-icon {
            font-size: 1.2rem;
        }

        .btn-text {
            flex: 1;
        }

        .notice {
            margin-top: 30px;
            padding: 15px;
            background: #fef3c7;
            border-radius: 8px;
            color: #92400e;
            font-size: 0.9rem;
            text-align: left;
            border-left: 4px solid #f59e0b;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes bounceIn {
            0% { transform: scale(0.5); opacity: 0; }
            60% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(1); }
        }

        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 1; }
            50% { transform: scale(1); opacity: 0.7; }
            100% { transform: scale(0.95); opacity: 1; }
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            .success-container {
                border-radius: 15px;
            }

            .success-header {
                padding: 30px 20px;
            }

            .success-body {
                padding: 30px 20px;
            }

            h1 {
                font-size: 1.8rem;
            }

            .buttons-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 400px) {
            h1 {
                font-size: 1.6rem;
            }

            .message {
                font-size: 1rem;
            }

            .btn {
                padding: 12px 15px;
            }
        }
    </style>
</head>
<body>
<div class="success-container">
    <div class="success-header">
        <div class="success-icon"></div>
        <h1>Operation Successful!</h1>
        <p class="message">Your request has been processed successfully.</p>
    </div>

    <div class="success-body">

{{--        <div class="buttons-container">--}}
{{--            <button class="btn btn-secondary">--}}
{{--                <span class="btn-icon">🏠</span>--}}
{{--                <span class="btn-text">Return Home</span>--}}
{{--            </button>--}}
{{--        </div>--}}

    </div>
</div>
</body>
</html>
