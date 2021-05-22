{{--@php--}}
{{--    $saved = [5,6,10,9,8,];--}}
{{--    $favorites = [10,9,8,7];--}}

{{--    $add = array_values(array_diff($saved, $favorites));--}}
{{--    $remove = array_values(array_diff($favorites, $saved));--}}

{{--    $exists = [5,7];--}}

{{--    dd($add, $remove, array_diff($add, $exists), array_intersect($exists, $remove));--}}
{{--@endphp--}}
<html>
    <head>
        <link rel="https://cdn.rawgit.com/mfd/f3d96ec7f0e8f034cc22ea73b3797b59/raw/856f1dbb8d807aabceb80b6d4f94b464df461b3e/gotham.css">
        <style>
            button {
                background-color: #1DB954; /* Green */
                border: none;
                color: white;
                padding: 15px 32px;
                border-radius: 50px;
                text-align: center;
                text-decoration: none;
                display: inline-block;
                font-size: 100px;
            }
            body, html {
                height: 100%;
                font-family: 'Gotham', serif;
                display: grid;
                background-color: #191414;
            }

            main {
                margin: auto;
            }

            .alert {
                text-align: center;
                display: block;
                font-size: 30px;
            }

            .alert__success {
                color: #1DB954;
            }

            .alert__wait {
                color: #ffe62e;
            }

            .alert__error {
                color: #e92a2a;
            }
        </style>
    </head>
    <body>
    <main>

        <div>
            <img src="https://cdn.usbrandcolors.com/images/logos/spotify-logo.svg" id="auth_btn" />
        </div>

        @isset($success)
            @if($success)
                <div class="alert alert__success">Успешно вошли!️</div>
                <div class="alert alert__wait">Начинаю синхронизацию️</div>
            @else
                <div class="alert alert__error">Произошла ошибка</div>
            @endif
        @endisset

    </main>
        <script>
            let btn = document.getElementById('auth_btn')
            btn.addEventListener('click', authorize)

            function authorize(e) {
                fetch('/authorize', {method: 'POST'})
                    .then(response => response.json())
                    .then(json => {
                        window.location = json.url
                    })
            }
        </script>
    </body>
</html>
