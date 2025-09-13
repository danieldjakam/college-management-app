<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BulletinTemplate;

class BulletinTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Bulletin de Séquence Standard',
                'type' => 'sequence',
                'template_html' => $this->getSequenceTemplate(),
                'css_styles' => $this->getSequenceCSS(),
                'is_active' => true,
                'description' => 'Template standard pour les bulletins de séquence (1 et 3)'
            ],
            [
                'name' => 'Bulletin de Trimestre Standard',
                'type' => 'trimester',
                'template_html' => $this->getTrimesterTemplate(),
                'css_styles' => $this->getTrimesterCSS(),
                'is_active' => true,
                'description' => 'Template standard pour les bulletins de trimestre'
            ],
            [
                'name' => 'Bulletin Annuel Standard',
                'type' => 'annual',
                'template_html' => $this->getAnnualTemplate(),
                'css_styles' => $this->getAnnualCSS(),
                'is_active' => true,
                'description' => 'Template standard pour le bulletin annuel'
            ],
            [
                'name' => 'Tableau d\'Honneur Standard',
                'type' => 'honor_roll',
                'template_html' => $this->getHonorRollTemplate(),
                'css_styles' => $this->getHonorRollCSS(),
                'is_active' => true,
                'description' => 'Template standard pour le tableau d\'honneur'
            ]
        ];

        foreach ($templates as $template) {
            BulletinTemplate::create($template);
        }
    }

    private function getSequenceTemplate()
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bulletin de Séquence</title>
</head>
<body>
    <div class="header">
        <div class="republic-header">
            <div class="left-section">
                <p><strong>REPUBLIQUE DU CAMEROUN</strong></p>
                <p>Paix - Travail - Patrie</p>
                <p><strong>MINISTERE DES ENSEIGNEMENTS SECONDAIRES</strong></p>
                <p>COLLEGE POLYVALENT BILINGUE DE DOUALA</p>
            </div>
            <div class="logo-section">
                <div class="school-logo"></div>
            </div>
            <div class="right-section">
                <p><strong>REPUBLIC OF CAMEROON</strong></p>
                <p>Peace - Work - Fatherland</p>
                <p><strong>MINISTRY OF SECONDARY EDUCATION</strong></p>
                <p>BILINGUAL COMPREHENSIVE COLLEGE DOUALA</p>
            </div>
        </div>
        
        <div class="bulletin-title">
            <h2>BULLETIN DE NOTES</h2>
            <p>ÉVALUATION N° {{sequence_number}}</p>
        </div>
        
        <div class="student-info">
            <table class="info-table">
                <tr>
                    <td>Nom et Prénom: <strong>{{student_first_name}} {{student_last_name}}</strong></td>
                    <td>N° ID Unique: {{student_id}}</td>
                </tr>
                <tr>
                    <td>Date de naissance: {{student_birth_date}}</td>
                    <td>Matricule: {{student_matricule}}</td>
                </tr>
                <tr>
                    <td>Classe: <strong>{{class_name}}</strong></td>
                    <td>Effectif: {{class_size}}</td>
                </tr>
            </table>
        </div>
    </div>
    
    <table class="grades-table">
        <thead>
            <tr>
                <th rowspan="2">DISCIPLINES/ENSEIGNANT</th>
                <th rowspan="2">NOTES/20</th>
                <th rowspan="2">COEF</th>
                <th rowspan="2">TOTAL</th>
                <th rowspan="2">RANG</th>
                <th rowspan="2">GRADE</th>
                <th colspan="2">APPRECIATIONS</th>
            </tr>
            <tr>
                <th>Min-Max</th>
                <th>APPRÉCIATION</th>
            </tr>
        </thead>
        <tbody>
            {{subjects_rows}}
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td><strong>TOTAL GÉNÉRAL</strong></td>
                <td></td>
                <td><strong>{{total_coefficient}}</strong></td>
                <td><strong>{{total_points}}</strong></td>
                <td><strong>{{rank}}/{{class_size}}</strong></td>
                <td><strong>{{mention}}</strong></td>
                <td colspan="2"><strong>{{appreciation}}</strong></td>
            </tr>
        </tfoot>
    </table>
    
    <div class="footer-info">
        <div class="averages">
            <p>MOYENNE GÉNÉRALE: <strong>{{average}}/20</strong></p>
            <p>MOY. PREMIER: {{first_average}}/20 - MOY. DERNIER: {{last_average}}/20</p>
        </div>
        
        <div class="signatures">
            <div class="signature">
                <p>VISA À HOMS DU PARENT</p>
                <br><br>
                <p>________________________</p>
            </div>
            <div class="signature">
                <p>LE PRINCIPAL</p>
                <br><br>
                <p>________________________</p>
            </div>
        </div>
    </div>
</body>
</html>';
    }

    private function getSequenceCSS()
    {
        return '@page { 
            size: A4; 
            margin: 1cm; 
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 10px;
            line-height: 1.2;
        }
        
        .header {
            margin-bottom: 15px;
        }
        
        .republic-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            font-size: 10px;
        }
        
        .left-section, .right-section {
            width: 40%;
        }
        
        .left-section {
            text-align: left;
        }
        
        .right-section {
            text-align: right;
        }
        
        .logo-section {
            width: 20%;
            text-align: center;
        }
        
        .school-logo {
            width: 50px;
            height: 50px;
            border: 2px solid #000;
            border-radius: 50%;
            margin: 0 auto;
        }
        
        .bulletin-title {
            text-align: center;
            margin: 15px 0;
        }
        
        .bulletin-title h2 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }
        
        .bulletin-title p {
            margin: 5px 0 0 0;
            font-size: 12px;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .info-table td {
            border: 1px solid #000;
            padding: 5px;
            font-size: 10px;
        }
        
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .grades-table th,
        .grades-table td {
            border: 1px solid #000;
            padding: 4px 2px;
            text-align: center;
            font-size: 9px;
        }
        
        .grades-table th {
            background-color: #e0e0e0;
            font-weight: bold;
        }
        
        .grades-table tbody td:first-child {
            text-align: left;
            font-size: 8px;
        }
        
        .total-row {
            background-color: #d0d0d0;
            font-weight: bold;
        }
        
        .footer-info {
            margin-top: 15px;
        }
        
        .averages {
            text-align: center;
            margin-bottom: 20px;
            font-size: 10px;
        }
        
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .signature {
            text-align: center;
            width: 45%;
            font-size: 9px;
        }
        
        .signature p {
            margin: 5px 0;
        }
        
        strong {
            font-weight: bold;
        }';
    }

    private function getTrimesterTemplate()
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bulletin de Trimestre</title>
</head>
<body>
    <div class="header">
        <div class="republic-header">
            <div class="left-section">
                <p><strong>REPUBLIQUE DU CAMEROUN</strong></p>
                <p>Paix - Travail - Patrie</p>
                <p><strong>MINISTERE DES ENSEIGNEMENTS SECONDAIRES</strong></p>
                <p>COLLEGE POLYVALENT BILINGUE DE DOUALA</p>
            </div>
            <div class="logo-section">
                <div class="school-logo"></div>
            </div>
            <div class="right-section">
                <p><strong>REPUBLIC OF CAMEROON</strong></p>
                <p>Peace - Work - Fatherland</p>
                <p><strong>MINISTRY OF SECONDARY EDUCATION</strong></p>
                <p>BILINGUAL COMPREHENSIVE COLLEGE DOUALA</p>
            </div>
        </div>
        
        <div class="bulletin-title">
            <h2>BULLETIN DE NOTES</h2>
            <p>TRIMESTRE N°{{trimester_number}}</p>
        </div>
        
        <div class="student-info">
            <table class="info-table">
                <tr>
                    <td>Nom et Prénom: <strong>{{student_first_name}} {{student_last_name}}</strong></td>
                    <td>N° ID Unique: {{student_id}}</td>
                </tr>
                <tr>
                    <td>Date de naissance: {{student_birth_date}}</td>
                    <td>Matricule: {{student_matricule}}</td>
                </tr>
                <tr>
                    <td>Classe: <strong>{{class_name}}</strong></td>
                    <td>Effectif: {{class_size}}</td>
                </tr>
                <tr>
                    <td>Nom Prof Princ: {{main_teacher}}</td>
                    <td>Redoublant: {{is_repeat_student}}</td>
                </tr>
            </table>
        </div>
    </div>
    
    <table class="grades-table trimester-table">
        <thead>
            <tr>
                <th rowspan="2">DISCIPLINES/ENSEIGNANT</th>
                <th colspan="3">COMPETENCES EVALUEES</th>
                <th rowspan="2">DS{{ds_number}}</th>
                <th rowspan="2">COMPO{{trimester_number}}</th>
                <th rowspan="2">MOY/20</th>
                <th rowspan="2">COEF</th>
                <th rowspan="2">TOTAL</th>
                <th rowspan="2">Rang</th>
                <th rowspan="2">Grade</th>
                <th rowspan="2">Min-Max</th>
                <th rowspan="2">Appréciation</th>
            </tr>
            <tr>
                <th>C1</th>
                <th>C2</th>
                <th>C3</th>
            </tr>
        </thead>
        <tbody>
            {{subjects_rows}}
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4"><strong>TOTAL</strong></td>
                <td></td>
                <td></td>
                <td><strong>{{average}}</strong></td>
                <td><strong>{{total_coefficient}}</strong></td>
                <td><strong>{{total_points}}</strong></td>
                <td><strong>{{rank}}</strong></td>
                <td><strong>{{mention}}</strong></td>
                <td><strong>Min Gén Cl:{{min_class}}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    
    <div class="footer-info">
        <div class="averages">
            <p>MOYENNE GÉNÉRALE: <strong>{{average}}/20</strong> - RANG: <strong>{{rank}}/{{class_size}}</strong></p>
        </div>
        
        <div class="signatures">
            <div class="signature">
                <p>VISA À HOMS DU PARENT</p>
                <br><br>
                <p>________________________</p>
            </div>
            <div class="signature">
                <p>LE PRINCIPAL</p>
                <br><br>
                <p>________________________</p>
            </div>
        </div>
    </div>
</body>
</html>';
    }

    private function getTrimesterCSS()
    {
        return $this->getSequenceCSS(); // Same CSS as sequence for now
    }

    private function getAnnualTemplate()
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Bulletin Annuel</title>
        </head>
        <body>
            <div class="bulletin-header">
                <div class="school-info">
                    <h2>{{school_name}}</h2>
                    <p>{{school_address}}</p>
                </div>
                <div class="bulletin-title">
                    <h1>BULLETIN ANNUEL</h1>
                    <h3>ANNÉE SCOLAIRE {{school_year}}</h3>
                </div>
            </div>
            
            <div class="student-info">
                <div class="left-info">
                    <p><strong>Nom:</strong> {{student_name}}</p>
                    <p><strong>Classe:</strong> {{class_name}}</p>
                    <p><strong>Matricule:</strong> {{student_matricule}}</p>
                </div>
                <div class="right-info">
                    <p><strong>Effectif:</strong> {{class_size}}</p>
                    <p><strong>Rang:</strong> {{rank}}/{{class_size}}</p>
                    <p><strong>Moyenne Annuelle:</strong> {{annual_average}}/20</p>
                </div>
            </div>
            
            <table class="annual-table">
                <thead>
                    <tr>
                        <th>DISCIPLINES</th>
                        <th>TRIM 1</th>
                        <th>TRIM 2</th>
                        <th>TRIM 3</th>
                        <th>MOY ANN</th>
                        <th>COEF</th>
                        <th>TOTAL</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    {{subjects_rows}}
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td><strong>TOTAL</strong></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td><strong>{{annual_average}}</strong></td>
                        <td><strong>{{total_coefficient}}</strong></td>
                        <td><strong>{{total_points}}</strong></td>
                        <td><strong>{{mention}}</strong></td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="decision">
                <p><strong>DÉCISION:</strong> {{decision}}</p>
            </div>
            
            <div class="footer">
                <div class="signatures">
                    <div class="signature">
                        <p>Le Principal</p>
                        <br><br>
                        <p>_________________</p>
                    </div>
                    <div class="signature">
                        <p>Le Parent</p>
                        <br><br>
                        <p>_________________</p>
                    </div>
                </div>
            </div>
        </body>
        </html>';
    }

    private function getAnnualCSS()
    {
        return $this->getSequenceCSS(); // Same CSS as sequence for now
    }

    private function getHonorRollTemplate()
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Tableau d\'Honneur</title>
        </head>
        <body>
            <div class="honor-certificate">
                <div class="header">
                    <h1>{{school_name}}</h1>
                    <p>{{school_address}}</p>
                </div>
                
                <div class="certificate-title">
                    <h2>TABLEAU D\'HONNEUR</h2>
                    <h3>ANNÉE SCOLAIRE {{school_year}}</h3>
                </div>
                
                <div class="certificate-body">
                    <p>Décerne le présent</p>
                    <h3>CERTIFICAT D\'HONNEUR</h3>
                    <p>À l\'élève</p>
                    
                    <div class="student-name">
                        <h2>{{student_name}}</h2>
                    </div>
                    
                    <p>De la classe de <strong>{{class_name}}</strong></p>
                    <p>Pour avoir obtenu une moyenne annuelle de <strong>{{annual_average}}/20</strong></p>
                    
                    <div class="congratulations">
                        <p><em>Félicitations pour ces excellents résultats !</em></p>
                    </div>
                </div>
                
                <div class="footer">
                    <div class="date">
                        <p>Fait à {{city}}, le {{date}}</p>
                    </div>
                    <div class="signature">
                        <p>Le Principal</p>
                        <br><br><br>
                        <p>_________________</p>
                        <p>{{principal_name}}</p>
                    </div>
                </div>
            </div>
        </body>
        </html>';
    }

    private function getHonorRollCSS()
    {
        return '
        body {
            font-family: "Times New Roman", serif;
            margin: 40px;
            background: linear-gradient(45deg, #f9f9f9, #ffffff);
        }
        
        .honor-certificate {
            border: 3px solid #gold;
            padding: 40px;
            text-align: center;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #0066cc;
            font-size: 24px;
            margin: 0;
        }
        
        .certificate-title h2 {
            color: #cc0000;
            font-size: 28px;
            margin: 30px 0;
            text-decoration: underline;
        }
        
        .student-name h2 {
            color: #0066cc;
            font-size: 32px;
            margin: 20px 0;
            text-decoration: underline;
        }
        
        .congratulations {
            margin: 30px 0;
            font-size: 16px;
            color: #cc0000;
        }
        
        .footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        
        .signature {
            text-align: center;
        }';
    }
}
