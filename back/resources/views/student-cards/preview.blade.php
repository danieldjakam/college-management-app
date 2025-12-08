<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prévisualisation Carte - {{ $card['prenom'] }} {{ $card['nom'] }}</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .preview-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .card-wrapper {
            width: 85.6mm;
            height: 54mm;
            position: relative;
            border: 2px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }

        .card {
            width: 100%;
            height: 100%;
            position: relative;
        }

        /* Image de fond (template) */
        .card-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .card-background img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Couche de données superposées */
        .card-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
        }

        /* Photo élève - en haut à droite (cercle) */
        .student-photo {
            position: absolute;
            top: {{ $settings['photo_top'] ?? '11' }}mm;
            right: {{ $settings['photo_right'] ?? '8' }}mm;
            width: {{ $settings['photo_width'] ?? '24' }}mm;
            height: {{ $settings['photo_height'] ?? '24' }}mm;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #2d5a3d;
            background: #f0f0f0;
        }

        .student-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Informations élève - VALEURS SEULEMENT */
        .student-info {
            position: absolute;
            top: {{ $settings['info_top'] ?? '20.5' }}mm;
            left: {{ $settings['info_left'] ?? '33' }}mm;
            font-size: {{ $settings['info_font_size'] ?? '4.5' }}pt;
            line-height: {{ $settings['info_line_height'] ?? '3.8' }}mm;
            letter-spacing: {{ $settings['info_letter_spacing'] ?? '0' }}px;
            color: #000;
            font-weight: 500;
            max-width: {{ $settings['info_max_width'] ?? '40' }}mm;
        }

        .info-value {
            display: block;
            color: #000;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            /* Empêcher le débordement */
        }

        /* QR Code - en bas au centre */
        .qr-code {
            position: absolute;
            bottom: {{ $settings['qr_bottom'] ?? '4' }}mm;
            left: calc(50% + {{ $settings['qr_left_offset'] ?? '0' }}mm);
            transform: translateX(-50%);
            width: {{ $settings['qr_width'] ?? '14' }}mm;
            height: {{ $settings['qr_height'] ?? '14' }}mm;
        }

        .qr-code img {
            width: 100%;
            height: 100%;
            display: block;
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .info-text {
            text-align: center;
            color: #666;
            margin-top: 15px;
            font-size: 12px;
        }

        .controls-panel {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-top: 20px;
            max-width: 600px;
        }

        .control-group {
            margin-bottom: 15px;
        }

        .control-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
            font-size: 13px;
        }

        .control-group input[type="range"] {
            width: 100%;
        }

        .control-group .value-display {
            display: inline-block;
            margin-left: 10px;
            font-weight: bold;
            color: #2563eb;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            margin-right: 10px;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <h2>Prévisualisation de la Carte</h2>

        <div class="card-wrapper">
            <div class="card">
                <!-- Image de fond (template) -->
                <div class="card-background">
                    <img src="{{ asset('images/carte-template.png') }}" alt="Template">
                </div>

                <!-- Données superposées -->
                <div class="card-overlay">
                    <!-- Photo élève -->
                    <div class="student-photo">
                        @if($card['photo_url'])
                            <img src="{{ $card['photo_url'] }}" alt="Photo élève">
                        @endif
                    </div>

                    <!-- Informations élève - VALEURS SEULEMENT -->
                    <div class="student-info">
                        <span class="info-value">{{ $card['nom'] }}</span>
                        <span class="info-value">{{ $card['prenom'] }}</span>
                        <span class="info-value">{{ $card['matricule'] }}</span>
                        <span class="info-value">{{ $card['classe'] }}</span>
                        <span class="info-value">{{ $card['date_naissance'] }}</span>
                        <span class="info-value">{{ $card['parent_contact'] }}</span>
                        <span class="info-value">{{ $academicYear }}</span>
                    </div>

                    <!-- QR Code -->
                    <div class="qr-code">
                        <img src="data:image/svg+xml;base64,{{ $card['qr_code'] }}" alt="QR Code">
                    </div>
                </div>
            </div>
        </div>

        <p class="info-text">
            <strong>{{ $card['prenom'] }} {{ $card['nom'] }}</strong><br>
            Classe: {{ $card['classe'] }} | Matricule: {{ $card['matricule'] }}
        </p>

        <!-- Panneau de contrôle -->
        <div class="controls-panel">
            <h3 style="margin-top: 0; margin-bottom: 20px;">Ajuster la mise en page</h3>

            <h4>Photo</h4>
            <div class="control-group">
                <label>Position Top: <span class="value-display" id="photo-top-value">{{ $settings['photo_top'] ?? '11' }} mm</span></label>
                <input type="range" id="photo-top" min="0" max="30" step="0.5" value="{{ $settings['photo_top'] ?? '11' }}">
            </div>
            <div class="control-group">
                <label>Position Right: <span class="value-display" id="photo-right-value">{{ $settings['photo_right'] ?? '8' }} mm</span></label>
                <input type="range" id="photo-right" min="0" max="30" step="0.5" value="{{ $settings['photo_right'] ?? '8' }}">
            </div>
            <div class="control-group">
                <label>Largeur: <span class="value-display" id="photo-width-value">{{ $settings['photo_width'] ?? '24' }} mm</span></label>
                <input type="range" id="photo-width" min="15" max="35" step="0.5" value="{{ $settings['photo_width'] ?? '24' }}">
            </div>
            <div class="control-group">
                <label>Hauteur: <span class="value-display" id="photo-height-value">{{ $settings['photo_height'] ?? '24' }} mm</span></label>
                <input type="range" id="photo-height" min="15" max="35" step="0.5" value="{{ $settings['photo_height'] ?? '24' }}">
            </div>

            <h4 style="margin-top: 20px;">Informations texte</h4>
            <div class="control-group">
                <label>Position Top: <span class="value-display" id="info-top-value">{{ $settings['info_top'] ?? '20.5' }} mm</span></label>
                <input type="range" id="info-top" min="0" max="40" step="0.5" value="{{ $settings['info_top'] ?? '20.5' }}">
            </div>
            <div class="control-group">
                <label>Position Left: <span class="value-display" id="info-left-value">{{ $settings['info_left'] ?? '33' }} mm</span></label>
                <input type="range" id="info-left" min="0" max="60" step="0.5" value="{{ $settings['info_left'] ?? '33' }}">
            </div>
            <div class="control-group">
                <label>Taille de police: <span class="value-display" id="info-font-size-value">{{ $settings['info_font_size'] ?? '4.5' }} pt</span></label>
                <input type="range" id="info-font-size" min="3" max="8" step="0.1" value="{{ $settings['info_font_size'] ?? '4.5' }}">
            </div>
            <div class="control-group">
                <label>Espacement des lignes: <span class="value-display" id="info-line-height-value">{{ $settings['info_line_height'] ?? '3.8' }} mm</span></label>
                <input type="range" id="info-line-height" min="2.5" max="6" step="0.1" value="{{ $settings['info_line_height'] ?? '3.8' }}">
            </div>
            <div class="control-group">
                <label>Largeur maximale: <span class="value-display" id="info-max-width-value">{{ $settings['info_max_width'] ?? '40' }} mm</span></label>
                <input type="range" id="info-max-width" min="20" max="60" step="1" value="{{ $settings['info_max_width'] ?? '40' }}">
            </div>
            <div class="control-group">
                <label>Espacement lettres (serrer/élargir): <span class="value-display" id="info-letter-spacing-value">{{ $settings['info_letter_spacing'] ?? '0' }} px</span></label>
                <input type="range" id="info-letter-spacing" min="-2" max="2" step="0.1" value="{{ $settings['info_letter_spacing'] ?? '0' }}">
            </div>

            <h4 style="margin-top: 20px;">Code QR</h4>
            <div class="control-group">
                <label>Position Bottom: <span class="value-display" id="qr-bottom-value">{{ $settings['qr_bottom'] ?? '4' }} mm</span></label>
                <input type="range" id="qr-bottom" min="0" max="20" step="0.5" value="{{ $settings['qr_bottom'] ?? '4' }}">
            </div>
            <div class="control-group">
                <label>Décalage Horizontal (Gauche ← → Droite): <span class="value-display" id="qr-left-offset-value">{{ $settings['qr_left_offset'] ?? '0' }} mm</span></label>
                <input type="range" id="qr-left-offset" min="-30" max="30" step="0.5" value="{{ $settings['qr_left_offset'] ?? '0' }}">
            </div>
            <div class="control-group">
                <label>Taille QR: <span class="value-display" id="qr-width-value">{{ $settings['qr_width'] ?? '14' }} mm</span></label>
                <input type="range" id="qr-width" min="10" max="25" step="0.5" value="{{ $settings['qr_width'] ?? '14' }}">
            </div>

            <div style="margin-top: 20px;">
                <button class="btn btn-primary" onclick="saveSettings()">Enregistrer ces paramètres</button>
                <button class="btn btn-secondary" onclick="resetSettings()">Réinitialiser</button>
            </div>
        </div>
    </div>

    <script>
        // Fonction pour mettre à jour les styles CSS dynamiquement
        function updateStyle(className, property, value, unit = 'mm') {
            const elements = document.querySelectorAll('.' + className);
            elements.forEach(el => {
                el.style[property] = value + unit;
            });
        }

        // Listeners pour les sliders
        document.getElementById('photo-top').addEventListener('input', function(e) {
            const value = e.target.value;
            document.getElementById('photo-top-value').textContent = value + ' mm';
            updateStyle('student-photo', 'top', value);
        });

        document.getElementById('photo-right').addEventListener('input', function(e) {
            const value = e.target.value;
            document.getElementById('photo-right-value').textContent = value + ' mm';
            updateStyle('student-photo', 'right', value);
        });

        document.getElementById('photo-width').addEventListener('input', function(e) {
            const value = e.target.value;
            document.getElementById('photo-width-value').textContent = value + ' mm';
            updateStyle('student-photo', 'width', value);
        });

        document.getElementById('photo-height').addEventListener('input', function(e) {
            const value = e.target.value;
            document.getElementById('photo-height-value').textContent = value + ' mm';
            updateStyle('student-photo', 'height', value);
        });

        document.getElementById('info-top').addEventListener('input', function(e) {
            const value = e.target.value;
            document.getElementById('info-top-value').textContent = value + ' mm';
            updateStyle('student-info', 'top', value);
        });

        document.getElementById('info-left').addEventListener('input', function(e) {
            const value = e.target.value;
            document.getElementById('info-left-value').textContent = value + ' mm';
            updateStyle('student-info', 'left', value);
        });

        document.getElementById('info-font-size').addEventListener('input', function(e) {
            const value = e.target.value;
            document.getElementById('info-font-size-value').textContent = value + ' pt';
            updateStyle('student-info', 'fontSize', value, 'pt');
        });

        document.getElementById('info-line-height').addEventListener('input', function(e) {
            const value = e.target.value;
            document.getElementById('info-line-height-value').textContent = value + ' mm';
            updateStyle('student-info', 'lineHeight', value);
        });

        document.getElementById('info-max-width').addEventListener('input', function(e) {
            const value = e.target.value;
            document.getElementById('info-max-width-value').textContent = value + ' mm';
            updateStyle('student-info', 'maxWidth', value);
        });

        document.getElementById('info-letter-spacing').addEventListener('input', function(e) {
            const value = e.target.value;
            document.getElementById('info-letter-spacing-value').textContent = value + ' px';
            updateStyle('student-info', 'letterSpacing', value, 'px');
        });

        document.getElementById('qr-bottom').addEventListener('input', function(e) {
            const value = e.target.value;
            document.getElementById('qr-bottom-value').textContent = value + ' mm';
            updateStyle('qr-code', 'bottom', value);
        });

        document.getElementById('qr-left-offset').addEventListener('input', function(e) {
            const value = e.target.value;
            document.getElementById('qr-left-offset-value').textContent = value + ' mm';
            const elements = document.querySelectorAll('.qr-code');
            elements.forEach(el => {
                el.style.left = `calc(50% + ${value}mm)`;
            });
        });

        document.getElementById('qr-width').addEventListener('input', function(e) {
            const value = e.target.value;
            document.getElementById('qr-width-value').textContent = value + ' mm';
            updateStyle('qr-code', 'width', value);
            updateStyle('qr-code', 'height', value);
        });

        // Fonction pour enregistrer les paramètres
        async function saveSettings() {
            const settings = [
                { key: 'photo_top', value: document.getElementById('photo-top').value },
                { key: 'photo_right', value: document.getElementById('photo-right').value },
                { key: 'photo_width', value: document.getElementById('photo-width').value },
                { key: 'photo_height', value: document.getElementById('photo-height').value },
                { key: 'info_top', value: document.getElementById('info-top').value },
                { key: 'info_left', value: document.getElementById('info-left').value },
                { key: 'info_font_size', value: document.getElementById('info-font-size').value },
                { key: 'info_line_height', value: document.getElementById('info-line-height').value },
                { key: 'info_max_width', value: document.getElementById('info-max-width').value },
                { key: 'info_letter_spacing', value: document.getElementById('info-letter-spacing').value },
                { key: 'qr_bottom', value: document.getElementById('qr-bottom').value },
                { key: 'qr_left_offset', value: document.getElementById('qr-left-offset').value },
                { key: 'qr_width', value: document.getElementById('qr-width').value },
                { key: 'qr_height', value: document.getElementById('qr-width').value }, // Same as width
            ];

            try {
                const token = window.__JWT_TOKEN__ || '';
                const apiUrl = window.__API_BASE_URL__ || '/api';

                if (!token) {
                    alert('Token d\'authentification manquant. Veuillez rafraîchir la page.');
                    return;
                }

                const response = await fetch(`${apiUrl}/card-layout-settings/update`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token
                    },
                    body: JSON.stringify({ settings })
                });

                const data = await response.json();
                if (data.success) {
                    alert('Paramètres enregistrés avec succès!');
                } else {
                    alert('Erreur: ' + data.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'enregistrement: ' + error.message);
            }
        }

        // Fonction pour réinitialiser
        async function resetSettings() {
            if (!confirm('Réinitialiser tous les paramètres aux valeurs par défaut?')) {
                return;
            }

            try {
                const token = window.__JWT_TOKEN__ || '';
                const apiUrl = window.__API_BASE_URL__ || '/api';

                if (!token) {
                    alert('Token d\'authentification manquant. Veuillez rafraîchir la page.');
                    return;
                }

                const response = await fetch(`${apiUrl}/card-layout-settings/reset`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token
                    }
                });

                const data = await response.json();
                if (data.success) {
                    alert('Paramètres réinitialisés!');
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors de la réinitialisation: ' + error.message);
            }
        }
    </script>
</body>
</html>
