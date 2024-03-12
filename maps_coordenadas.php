<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa de Google</title>
    <style>
        #map {
            height: 400px;
            width: 100%;
        }
    </style>
</head>

<body>
    <div id="map"></div>

    <script>
        // Función para obtener parámetros de la URL
        function getParameterByName(name) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        }

        // Obtener las coordenadas de la URL
        const lat = getParameterByName('lat');
        const lng = getParameterByName('lng');

        // Función para inicializar el mapa con las coordenadas de la URL
        function initMap() {
            // Coordenadas iniciales para Colombia si no se encuentran en la URL
            const defaultCoords = { lat: 4.5709, lng: -74.2973 };

            // Si se encuentran las coordenadas en la URL, usarlas
            const mapCoords = lat && lng ? { lat: parseFloat(lat), lng: parseFloat(lng) } : defaultCoords;

            // Determinar el nivel de zoom
            const zoomLevel = lat && lng ? 10 : 5;

            // Opciones del mapa
            const mapOptions = {
                center: mapCoords,
                zoom: zoomLevel // Zoom inicial: 5 muestra Colombia completa, 10 para mayor detalle.
            };

            // Crear el mapa
            const map = new google.maps.Map(document.getElementById('map'), mapOptions);

            // Crear marcador inicial
            const marker = new google.maps.Marker({
                position: mapCoords,
                map: map,
                draggable: true // Permitir arrastrar el marcador
            });

            // Evento cuando se mueve el marcador
            google.maps.event.addListener(marker, 'dragend', function (event) {
                let lat = event.latLng.lat();
                let lng = event.latLng.lng();

                parent.document.getElementById('latGPS').value = lat;
                parent.document.getElementById('lngGPS').value = lng;

                parent.document.getElementById('coordGPS').innerText = `${lat}, ${lng}`;
            });
        }
    </script>

    <!-- Cargar la API de Google Maps de forma asíncrona -->
    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBoTS0NNUWQ75LxwIg5Y3kvNwUUDgLaWpY&callback=initMap&language=es"></script>
</body>

</html>