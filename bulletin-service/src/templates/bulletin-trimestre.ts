import {
  StudentBulletinData,
  SchoolInfo,
  SubjectGroup,
  SubjectGrade,
} from "../lib/types";

/**
 * Template HTML du bulletin trimestriel
 * Rendu côté serveur - pas de React, pur HTML/CSS pour Puppeteer
 */
export function renderBulletinTrimestre(
  data: StudentBulletinData,
  school: SchoolInfo
): string {
  const { student, groups, cycle_type } = data;

  return `<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <style>
    ${getBulletinStyles()}
  </style>
</head>
<body>
  <div class="bulletin-page">
    ${renderHeader(data, school)}
    ${renderStudentInfo(data)}
    ${renderGradesTable(data)}
    ${renderFooter(data)}
  </div>
</body>
</html>`;
}

function getBulletinStyles(): string {
  return `
    @page {
      size: A4 portrait;
      margin: 3mm;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Arial', 'Helvetica', sans-serif;
      font-size: 8pt;
      line-height: 1.2;
      color: #1a1a1a;
    }

    .bulletin-page {
      width: 210mm;
      min-height: 295mm;
      padding: 4mm;
      position: relative;
    }

    /* Header */
    .header-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 2mm;
    }

    .header-table td {
      vertical-align: top;
      font-size: 7pt;
      line-height: 1.2;
    }

    .header-left, .header-right {
      width: 32%;
      font-size: 6.5pt;
    }

    .header-center {
      width: 36%;
      text-align: center;
    }

    .header-left { text-align: left; }
    .header-right { text-align: right; }

    .school-name {
      font-size: 9pt;
      font-weight: bold;
      color: #009B3A;
      margin: 1mm 0;
    }

    .logo {
      max-width: 18mm;
      max-height: 18mm;
    }

    .separator {
      height: 2px;
      background: linear-gradient(to right, #009B3A 33%, #CE1126 33%, #CE1126 66%, #FCD116 66%);
      margin: 1mm 25mm;
    }

    .bulletin-title {
      text-align: center;
      font-size: 11pt;
      font-weight: bold;
      color: #009B3A;
      text-transform: uppercase;
      margin: 2mm 0 1mm;
      letter-spacing: 1px;
    }

    /* Student info */
    .student-info-table {
      width: 100%;
      border-collapse: collapse;
      margin: 1mm 0 2mm;
      font-size: 7.5pt;
    }

    .student-info-table td {
      padding: 0.8mm 2mm;
      border: 0.5px solid #ccc;
    }

    .info-label {
      font-weight: bold;
      background-color: #f0f7f0;
      width: 22%;
      color: #333;
    }

    .info-value {
      width: 28%;
    }

    /* Grades table */
    .grades-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 7pt;
      margin-top: 1mm;
    }

    .grades-table th,
    .grades-table td {
      border: 0.5px solid #999;
      padding: 0.8mm 1.2mm;
      text-align: center;
      vertical-align: middle;
    }

    .grades-table th {
      background-color: #2c5f2d;
      color: white;
      font-size: 6.5pt;
      font-weight: bold;
      text-transform: uppercase;
    }

    .grades-table .subject-name {
      text-align: left;
      font-weight: bold;
      padding-left: 2mm;
    }

    .grades-table .group-header {
      background-color: #e8f5e9;
      font-weight: bold;
      text-align: left;
      padding-left: 2mm;
      font-size: 7pt;
      color: #1b5e20;
    }

    .grades-table .group-total {
      background-color: #f1f8e9;
      font-weight: bold;
      font-size: 7pt;
    }

    .grades-table .general-row {
      background-color: #2c5f2d;
      color: white;
      font-weight: bold;
      font-size: 8pt;
    }

    .grades-table .good { color: #1b5e20; }
    .grades-table .bad { color: #c62828; }
    .grades-table .absent { color: #e65100; font-style: italic; }

    /* Footer */
    .footer-section {
      margin-top: 2mm;
    }

    .footer-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 7pt;
    }

    .footer-table td {
      padding: 1mm 2mm;
      vertical-align: top;
    }

    .appreciation-box {
      border: 0.5px solid #999;
      padding: 2mm;
      min-height: 15mm;
      font-size: 7.5pt;
    }

    .signature-block {
      text-align: center;
      padding-top: 3mm;
    }

    .signature-line {
      border-top: 1px solid #333;
      width: 35mm;
      margin: 8mm auto 0;
      padding-top: 1mm;
      font-size: 6pt;
      color: #666;
    }

    .stats-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 7pt;
      margin-top: 1mm;
    }

    .stats-table td {
      border: 0.5px solid #999;
      padding: 0.8mm 2mm;
    }

    .stats-label {
      font-weight: bold;
      background-color: #f0f7f0;
    }
  `;
}

function renderHeader(data: StudentBulletinData, school: SchoolInfo): string {
  const periodTitle =
    data.period_type === "trimester"
      ? `BULLETIN DE NOTES DU ${data.trimester_number === 1 ? "1er" : data.trimester_number + "ème"} TRIMESTRE`
      : data.period_type === "sequence"
        ? `BULLETIN DE NOTES - SÉQUENCE ${data.sequence_number}`
        : "BULLETIN ANNUEL";

  return `
    <table class="header-table">
      <tr>
        <td class="header-left">
          <strong>REPUBLIQUE DU CAMEROUN</strong><br>
          <em>Paix - Travail - Patrie</em><br>
          ★ ★ ★ ★ ★<br>
          MINISTÈRE DES ENSEIGNEMENTS<br>
          SECONDAIRES<br>
          ★ ★ ★ ★ ★<br>
          DÉLÉGATION RÉGIONALE<br>
          DU LITTORAL
        </td>
        <td class="header-center">
          ${
            school.logo_base64
              ? `<img src="${school.logo_base64}" class="logo" />`
              : `<div style="font-size:18pt;color:#009B3A;font-weight:bold;">CPB</div>`
          }
          <div class="school-name">${school.name || "COLLEGE POLYVALENT BILINGUE DE DOUALA"}</div>
          ${school.bp ? `<div style="font-size:6pt;">B.P: ${school.bp}</div>` : ""}
          ${school.phone ? `<div style="font-size:6pt;">Tél: ${school.phone}</div>` : ""}
        </td>
        <td class="header-right">
          <strong>REPUBLIC OF CAMEROON</strong><br>
          <em>Peace - Work - Fatherland</em><br>
          ★ ★ ★ ★ ★<br>
          MINISTRY OF SECONDARY<br>
          EDUCATION<br>
          ★ ★ ★ ★ ★<br>
          LITTORAL REGIONAL<br>
          DELEGATION
        </td>
      </tr>
    </table>
    <div class="separator"></div>
    <div class="bulletin-title">${periodTitle}</div>
    <div style="text-align:center;font-size:7pt;color:#666;">
      Année Scolaire / School Year: <strong>${data.school_year}</strong>
    </div>
  `;
}

function renderStudentInfo(data: StudentBulletinData): string {
  const { student } = data;
  return `
    <table class="student-info-table">
      <tr>
        <td class="info-label">Nom / Name</td>
        <td class="info-value"><strong>${student.last_name.toUpperCase()}</strong></td>
        <td class="info-label">Prénom / First Name</td>
        <td class="info-value">${student.first_name}</td>
      </tr>
      <tr>
        <td class="info-label">Classe / Class</td>
        <td class="info-value"><strong>${data.class_name}</strong></td>
        <td class="info-label">Matricule</td>
        <td class="info-value">${student.matricule || "N/A"}</td>
      </tr>
      <tr>
        <td class="info-label">Né(e) le / Born on</td>
        <td class="info-value">${formatDate(student.date_of_birth)}</td>
        <td class="info-label">Sexe / Gender</td>
        <td class="info-value">${student.gender === "Masculin" ? "M" : "F"}</td>
      </tr>
      <tr>
        <td class="info-label">Effectif / Enrollment</td>
        <td class="info-value">${data.total_students}</td>
        <td class="info-label">Redoublant(e)</td>
        <td class="info-value">${student.redoublant ? "Oui" : "Non"}</td>
      </tr>
    </table>
  `;
}

function renderGradesTable(data: StudentBulletinData): string {
  const isPremier = data.cycle_type === "premier";
  const isTrimester = data.period_type === "trimester";
  const isSequence = data.period_type === "sequence";

  // Build column headers based on cycle and period type
  let headers = "";

  if (isTrimester && isPremier) {
    headers = `
      <th style="width:20%;">Matières / Subjects</th>
      <th style="width:5%;">Coef</th>
      <th style="width:7%;">Moy. DS</th>
      <th style="width:7%;">Compo</th>
      <th style="width:7%;">Moy/20</th>
      <th style="width:8%;">Moy×Coef</th>
      <th style="width:5%;">Rang</th>
      <th style="width:13%;">Appréciation</th>
      <th style="width:5%;">Min</th>
      <th style="width:5%;">Max</th>
      <th style="width:5%;">Moy Cl</th>
      <th style="width:13%;">Enseignant</th>
    `;
  } else if (isTrimester && !isPremier) {
    headers = `
      <th style="width:18%;">Matières / Subjects</th>
      <th style="width:5%;">Coef</th>
      <th style="width:6%;">Seq ${((data.trimester_number || 1) - 1) * 2 + 1}</th>
      <th style="width:6%;">Seq ${((data.trimester_number || 1) - 1) * 2 + 2}</th>
      <th style="width:6%;">Compo</th>
      <th style="width:7%;">Moy/20</th>
      <th style="width:8%;">Moy×Coef</th>
      <th style="width:5%;">Rang</th>
      <th style="width:12%;">Appréciation</th>
      <th style="width:5%;">Min</th>
      <th style="width:5%;">Max</th>
      <th style="width:5%;">Moy Cl</th>
      <th style="width:12%;">Enseignant</th>
    `;
  } else {
    // Sequence
    headers = `
      <th style="width:22%;">Matières / Subjects</th>
      <th style="width:6%;">Coef</th>
      <th style="width:8%;">Note/20</th>
      <th style="width:9%;">Note×Coef</th>
      <th style="width:6%;">Rang</th>
      <th style="width:15%;">Appréciation</th>
      <th style="width:6%;">Min</th>
      <th style="width:6%;">Max</th>
      <th style="width:6%;">Moy Cl</th>
      <th style="width:16%;">Enseignant</th>
    `;
  }

  let rows = "";
  for (const group of data.groups) {
    rows += renderGroupRows(group, data);
  }

  // General average row
  const avgClass = data.general_average !== null && data.general_average >= 10 ? "good" : "bad";
  const generalRow = `
    <tr class="general-row">
      <td colspan="2" style="text-align:right;padding-right:3mm;">MOYENNE GÉNÉRALE / GENERAL AVERAGE</td>
      ${isTrimester && !isPremier ? '<td></td><td></td>' : isTrimester ? '<td></td>' : ''}
      <td></td>
      <td class="${avgClass}" style="color:white;font-size:9pt;">${formatNumber(data.general_average)}</td>
      <td>${formatNumber(data.total_weighted)}</td>
      <td>${data.rank || "-"}${data.rank === 1 ? "er" : data.rank ? "è" : ""}</td>
      <td colspan="${isTrimester && !isPremier ? 6 : isTrimester ? 5 : 4}" style="text-align:left;padding-left:2mm;">
        Coef: ${data.total_coefficient}
      </td>
    </tr>
  `;

  return `
    <table class="grades-table">
      <thead>
        <tr>${headers}</tr>
      </thead>
      <tbody>
        ${rows}
        ${generalRow}
      </tbody>
    </table>
  `;
}

function renderGroupRows(group: SubjectGroup, data: StudentBulletinData): string {
  const isPremier = data.cycle_type === "premier";
  const isTrimester = data.period_type === "trimester";
  const colCount = isTrimester && !isPremier ? 13 : isTrimester ? 12 : 10;

  let html = `
    <tr>
      <td class="group-header" colspan="${colCount}">
        ${group.header} : ${group.name}
      </td>
    </tr>
  `;

  for (const subject of group.subjects) {
    html += renderSubjectRow(subject, data);
  }

  // Group total row
  html += `
    <tr class="group-total">
      <td style="text-align:right;padding-right:2mm;">Total ${group.code}</td>
      <td>${group.group_total_coef}</td>
      ${isTrimester && !isPremier ? '<td></td><td></td>' : isTrimester ? '<td></td>' : ''}
      <td></td>
      <td>${formatNumber(group.group_average)}</td>
      <td>${formatNumber(group.group_total_weighted)}</td>
      <td colspan="${isTrimester && !isPremier ? 7 : isTrimester ? 6 : 4}"></td>
    </tr>
  `;

  return html;
}

function renderSubjectRow(subject: SubjectGrade, data: StudentBulletinData): string {
  const isPremier = data.cycle_type === "premier";
  const isTrimester = data.period_type === "trimester";

  const avgClass =
    subject.average_on_20 !== null && subject.average_on_20 >= 10
      ? "good"
      : subject.average_on_20 !== null
        ? "bad"
        : "";

  if (subject.is_absent) {
    const absCols = isTrimester && !isPremier ? 11 : isTrimester ? 10 : 8;
    return `
      <tr>
        <td class="subject-name">${subject.subject_name}</td>
        <td>${subject.coefficient}</td>
        <td colspan="${absCols}" class="absent">ABS</td>
      </tr>
    `;
  }

  if (isTrimester && isPremier) {
    return `
      <tr>
        <td class="subject-name">${subject.subject_name}</td>
        <td>${subject.coefficient}</td>
        <td>${formatNumber(subject.ds_average)}</td>
        <td>${formatNumber(subject.composition)}</td>
        <td class="${avgClass}"><strong>${formatNumber(subject.average_on_20)}</strong></td>
        <td>${formatNumber(subject.weighted_average)}</td>
        <td>${subject.subject_rank || "-"}</td>
        <td style="font-size:6pt;text-align:left;">${subject.appreciation || ""}</td>
        <td>${formatNumber(subject.class_min)}</td>
        <td>${formatNumber(subject.class_max)}</td>
        <td>${formatNumber(subject.class_avg)}</td>
        <td style="font-size:5.5pt;text-align:left;">${subject.teacher_name || ""}</td>
      </tr>
    `;
  }

  if (isTrimester && !isPremier) {
    return `
      <tr>
        <td class="subject-name">${subject.subject_name}</td>
        <td>${subject.coefficient}</td>
        <td>${formatNumber(subject.seq1)}</td>
        <td>${formatNumber(subject.seq2)}</td>
        <td>${formatNumber(subject.composition)}</td>
        <td class="${avgClass}"><strong>${formatNumber(subject.average_on_20)}</strong></td>
        <td>${formatNumber(subject.weighted_average)}</td>
        <td>${subject.subject_rank || "-"}</td>
        <td style="font-size:6pt;text-align:left;">${subject.appreciation || ""}</td>
        <td>${formatNumber(subject.class_min)}</td>
        <td>${formatNumber(subject.class_max)}</td>
        <td>${formatNumber(subject.class_avg)}</td>
        <td style="font-size:5.5pt;text-align:left;">${subject.teacher_name || ""}</td>
      </tr>
    `;
  }

  // Sequence bulletin
  return `
    <tr>
      <td class="subject-name">${subject.subject_name}</td>
      <td>${subject.coefficient}</td>
      <td class="${avgClass}"><strong>${formatNumber(subject.average_on_20)}</strong></td>
      <td>${formatNumber(subject.weighted_average)}</td>
      <td>${subject.subject_rank || "-"}</td>
      <td style="font-size:6pt;text-align:left;">${subject.appreciation || ""}</td>
      <td>${formatNumber(subject.class_min)}</td>
      <td>${formatNumber(subject.class_max)}</td>
      <td>${formatNumber(subject.class_avg)}</td>
      <td style="font-size:5.5pt;text-align:left;">${subject.teacher_name || ""}</td>
    </tr>
  `;
}

function renderFooter(data: StudentBulletinData): string {
  return `
    <div class="footer-section">
      <table class="stats-table">
        <tr>
          <td class="stats-label">Moyenne Générale</td>
          <td><strong>${formatNumber(data.general_average)}/20</strong></td>
          <td class="stats-label">Rang</td>
          <td><strong>${data.rank || "-"}${data.rank === 1 ? "er" : data.rank ? "ème" : ""} / ${data.total_students}</strong></td>
          <td class="stats-label">Moy. Classe</td>
          <td>${formatNumber(data.class_average)}/20</td>
        </tr>
        <tr>
          <td class="stats-label">Plus forte moy.</td>
          <td>${formatNumber(data.class_max)}/20</td>
          <td class="stats-label">Plus faible moy.</td>
          <td>${formatNumber(data.class_min)}/20</td>
          <td class="stats-label">Taux de réussite</td>
          <td>${data.pass_rate !== null ? data.pass_rate + "%" : "-"}</td>
        </tr>
        ${
          data.absences_total !== undefined
            ? `<tr>
                <td class="stats-label">Absences totales</td>
                <td>${data.absences_total}h</td>
                <td class="stats-label">Abs. justifiées</td>
                <td>${data.absences_justified || 0}h</td>
                <td class="stats-label">Abs. non justifiées</td>
                <td>${(data.absences_total || 0) - (data.absences_justified || 0)}h</td>
              </tr>`
            : ""
        }
      </table>

      <table class="footer-table" style="margin-top:2mm;">
        <tr>
          <td style="width:55%;">
            <div class="appreciation-box">
              <strong>Appréciation du Conseil de Classe:</strong><br>
              ${data.conseil_de_classe || data.general_appreciation || ""}
            </div>
          </td>
          <td style="width:45%;">
            <div class="signature-block">
              <strong>Le Principal / The Principal</strong>
              <div class="signature-line">Signature & cachet</div>
            </div>
            ${
              data.main_teacher_name
                ? `<div class="signature-block" style="margin-top:3mm;">
                    <strong>Prof. Principal: ${data.main_teacher_name}</strong>
                    <div class="signature-line">Signature</div>
                  </div>`
                : ""
            }
          </td>
        </tr>
      </table>
    </div>
  `;
}

// Helpers
function formatNumber(n: number | null | undefined): string {
  if (n === null || n === undefined) return "-";
  return n.toFixed(2);
}

function formatDate(dateStr: string): string {
  if (!dateStr) return "N/A";
  const d = new Date(dateStr);
  return d.toLocaleDateString("fr-FR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
}
