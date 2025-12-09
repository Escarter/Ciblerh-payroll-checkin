<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="theme-color" content="#1F2937">

    <link rel="icon" href="{{ asset('img/fav.jpg') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('img/fav.jpg') }}" type="image/x-icon">
    <link rel="apple-touch-icon" href="{{ asset('img/fav.jpg') }}">

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&amp;display=swap" rel="stylesheet">

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>{{env('APP_NAME') ?? __('app.ciblerh')}} - Error</title>

    <meta name="msapplication-TileColor" content="#1F2937">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom Theme CSS -->
    <link type="text/css" href="{{ asset('css/theme.css')}}" rel="stylesheet">

    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-container {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
            margin: 2rem;
            border: 1px solid #e9ecef;
        }

        .error-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 2rem;
            opacity: 0.8;
        }

        .error-code {
            font-size: 6rem;
            font-weight: 700;
            color: #6c757d;
            margin-bottom: 1rem;
            line-height: 1;
        }

        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1rem;
        }

        .error-message {
            color: #6c757d;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .btn-custom {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .btn-custom:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
        }

        .btn-outline-custom {
            background-color: transparent;
            border: 1px solid #6c757d;
            color: #6c757d;
            padding: 11px 29px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            margin-left: 1rem;
            transition: all 0.2s ease;
        }

        .btn-outline-custom:hover {
            background-color: #6c757d;
            border-color: #6c757d;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }

        @media (max-width: 576px) {
            .error-container {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }

            .error-code {
                font-size: 4rem;
            }

            .btn-outline-custom {
                margin-left: 0;
                margin-top: 1rem;
                display: block;
            }
        }
    </style>
</head>

<body>
    <div class="error-container">
        {{ $slot }}
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>