import { Matiere } from "../models/Matiere";
var Json2csvParser = require('json2csv').Parser
const admZip = require('adm-zip');
const pdf = require('pdf-creator-node');
const optionsPdf = require('../../helpers/optionsPdf')
let optionsPdfL = require('../../helpers/optionsPdfLis')
const optionsPdfRecu = require('../../helpers/optionsPdfRecu')
const downloadFs = require('fs');

const months = [
    'Incorrect',
    'Janvier',
    'Fevrier',
    'Mars',
    'Avril',
    'Mai',
    'Juin',
    'Juillet',
    'Aout',
    'Septembre',
    'Octobre',
    'Novembre',
    'Decembre'
]
module.exports.downloadStudentsCsv = (req, res) => {

    req.connection.query(`SELECT students.name, students.subname, 
                            students.birthday, students.sex, fatherName, 
                            email, phone_number,
                            birthday_place, students.school_year, status,
                            class.name as class_name  FROM students 
                            JOIN class ON class.id = students.class_id 
                            WHERE class_id = ? AND is_new = "no"ORDER BY name ASC`, 
                            [req.params.id], function (err, oldStudents) {
        req.connection.query(`SELECT students.name, students.subname, 
                                students.birthday, students.sex, fatherName, 
                                email, phone_number,
                                birthday_place, students.school_year, status,
                                class.name as class_name  FROM students 
                                JOIN class ON class.id = students.class_id 
                                WHERE class_id = ? AND is_new = "no" ORDER BY name ASC`, 
            [req.params.id] , (err, newStudents) => {
                let users = [];
                if (newStudents.length > 0) {
                    users = [...oldStudents, ...newStudents]
                }
                else{
                    users = [...oldStudents]
                }
        
                if (err) throw err;
                const jsonUsers = JSON.parse(JSON.stringify(users));
                const csvFields = ['name', 'subname', 'birthday', 'sex', 'fatherName', 'email', 'phone_number', 'class_name', 'birthday_place', 'school_year', 'status'];
                const json2csvParser = new Json2csvParser({ csvFields });
                const csv = json2csvParser.parse(jsonUsers);
                const csvFile = `docs/eleves de ${jsonUsers[0].class_name}.csv`;
                downloadFs.writeFile(csvFile, csv, function (err, csv) {
                    if (err) return console.log(err);
                    else res.download(csvFile);
                });
        })
    });
}

module.exports.downloadStudentsPdf = (req, res) => {
    const array = JSON.parse(req.params.array);
    const html = downloadFs.readFileSync(`src/templates/studentsList${array.length > 0 ? '1' : ''}.html`, 'utf-8');
    let info_info = {
        name: array.includes('name') ? 1 : 0,
        subname: array.includes('subname') ? 1 : 0,
        birthday: array.includes('birthday') ? 1 : 0,
        birthday_place: array.includes('birthday_place') ? 1 : 0,
        phone_number: array.includes('phone_number') ? 1 : 0,
        sex: array.includes('sex') ? 1 : 0,
        profession: array.includes('profession') ? 1 : 0,
        fatherName: array.includes('fatherName') ? 1 : 0
    }
    req.connection.query("SELECT students.name, teachers.name as tName, teachers.subname as tSubname, students.subname,  students.email,  students.phone_number, students.birthday, students.sex, class.name as cName  FROM students LEFT JOIN class ON class.id = students.class_id LEFT JOIN teachers ON teachers.class_id = class.id WHERE students.class_id = ?", [req.params.id], function (err, students, fields) {
        if (err) console.log(err);
        const fileName = `Liste des eleves de ${students[0].cName}.pdf`;
        let info: {
            className: string,
            teacherName: string
            teacherSubname: string
        } = {
            className: students[0].cName,
            teacherName: students[0].tName,
            teacherSubname: students[0].tSubname,
        }
        let resp = [];
        req.connection.query('SELECT * FROM students WHERE class_id = ? ORDER BY name ASC', [req.params.id] , (err, oldStudents) => {
            
            if(err) console.log(err);
            else {
                req.connection.query('SELECT * FROM students WHERE class_id = ? AND is_new = "no" ORDER BY name ASC', 
                                        [req.params.id] , (err, oldStudents) => {
            
                    if(err) console.log(err);
                    else {
                        req.connection.query('SELECT * FROM students WHERE class_id = ? AND is_new = "yes"', 
                                            [req.params.id] , (err, newStudents) => {
                            if (newStudents.length > 0) {
                                resp = [...oldStudents, ...newStudents]
                            }
                            else{
                                resp = [...oldStudents]
                            }
                            if (resp.length > 0) {
                                resp.forEach((stud, id) => {
                                    stud.number = id + 1;
                                    stud.info = info_info;
                                    stud.birthday = stud.birthday ? new Date(stud.birthday).getDate() + ' ' + months[new Date(stud.birthday).getMonth()] + " " + new Date(stud.birthday).getUTCFullYear() : ''
                                })
                            }
                            
                            const document = {
                                html: html,
                                data: {
                                    y: 12,
                                    students: resp,
                                    info,
                                    info_info
                                },
                                path: `docs/${fileName}`
                            };
                            pdf.create(document, optionsPdfL)
                                .then(resp => {
                                    res.download(`docs/${fileName}`)
                                })
                                .catch(err => {
                                    console.log(err);
                                    res.status(201).json(err)
                                })
                        })
                    }
                })
            }
        })
    });
}

module.exports.downloadTable = (req, res) => {
    const html = downloadFs.readFileSync('src/templates/ageTable.html', 'utf-8');
    req.connection.query(`SELECT students.name, teachers.name as tName, teachers.subname as tSubname, 
                            students.subname,  students.email,  students.phone_number, students.birthday, 
                            class.last_date, class.first_date,
                            (select count(id) FROM students where students.class_id = class.id) as total_students,
                            students.sex, class.name as cName  FROM students LEFT JOIN class ON class.id = students.class_id 
                            LEFT JOIN teachers ON teachers.class_id = class.id WHERE students.class_id = ?`, 
                            [req.params.id], function (err, students, fields) {
        if (err) console.log(err);
        const fileName = `tableau des ages ${students[0].cName}.pdf`;
        let info: {
            className: string,
            teacherName: string,
            teacherSubname: string,
            boys: number
            girls: number
            total_students: 0
        } = {
            className: students[0].cName,
            teacherName: students[0].tName,
            teacherSubname: students[0].tSubname,
            boys: 0,
            girls: 0,
            total_students: students[0].total_students
        }
        let years = [];
        optionsPdfL.orientation = 'portrait';
        const ldate = students[0].last_date ? students[0].last_date : 2020;
        const fdate = students[0].first_date ? students[0].first_date : 2010;
        let resp = [];
        for (let i = fdate; i <= ldate; i++) {
            years.push(i);
        }
        req.connection.query('SELECT * FROM students WHERE class_id = ? ORDER BY name ASC', [req.params.id] , (err, oldStudents) => {
            
            if(err) console.log(err);
            else {
                req.connection.query('SELECT * FROM students WHERE class_id = ? AND is_new = "no" ORDER BY name ASC', 
                                        [req.params.id] , (err, oldStudents) => {
            
                    if(err) console.log(err);
                    else {
                        req.connection.query('SELECT * FROM students WHERE class_id = ? AND is_new = "yes"', 
                                            [req.params.id] , (err, newStudents) => {
                            if (newStudents.length > 0) {
                                resp = [...oldStudents, ...newStudents]
                            }
                            else{
                                resp = [...oldStudents]
                            }
                            if (resp.length > 0) {
                                info.boys = resp.filter(st => st.sex === 'm').length;
                                info.girls = resp.filter(st => st.sex === 'f').length;
                            }
                            let a = years[0];
                            let b = years[1];
                            let c = years[2];
                            let d = years[3];
                            let e = years[4];
                            let f = years[5];
                            let g = years[6];
                            let h = years[7];
                            let i = years[8];
                            let j = years[9];
                            let k = years[10];
                            let total = [];
                            resp.forEach(r => {
                                r.year = r.birthday;
                            })
                            years.forEach(yea => {
                                total[yea] = {
                                    total: 0,
                                    boys: 0,
                                    girls: 0
                                }
                            })
                            
                            const document = {
                                html: html,
                                data: {
                                    total,
                                    students: resp,
                                    info,
                                    years,
                                    a,b,c,d,e,f,g,h,i,j,k
                                },
                                path: `docs/${fileName}`
                            };
                            if (resp.length > 0) {
                                resp.forEach((stud, id) => {
                                    stud.number = id + 1;
                                    stud.birthday = parseInt(stud.birthday)
                                })
                            }
                            pdf.create(document, optionsPdfL)
                                .then(resp => {
                                    res.download(`docs/${fileName}`)
                                })
                                .catch(err => {
                                    console.log(err);
                                    res.status(201).json(err)
                                })
                        })
                    }
                })
            }
        })
    });
}

module.exports.downloadBulletin = (req, res) => {
    const { exam_id, student_id, class_id } = req.params;
    const html = downloadFs.readFileSync('src/templates/Bulletin.html', 'utf-8');
    let badCompetence: string[] = [];
    let totalPoint: number = 0;
    let totalNote: number
    let diviser: number = 0;

    req.connection.query('SELECT * FROM students WHERE class_id = ?', [class_id], (errr, allStudents: []) => {
        req.connection.query("SELECT students.name, teachers.name as tName, teachers.subname as tSubname, students.subname, students.birthday, students.sex, class.name as cName  FROM students LEFT JOIN class ON class.id = students.class_id LEFT JOIN teachers ON teachers.class_id = class.id WHERE students.id = ?", [student_id], function (err, student, fields) {
            if (err) console.log(err);
            const stud: {
                name: string,
                subname: string,
                cName: string,
                tName: string,
                tSubname: string,
                sex: string,
                birthday: string,
            } = student[0];
            let info: {
                className: string,
                teacherName: string
                teacherSubname: string
            } = {
                className: stud.cName,
                teacherName: stud.tName,
                teacherSubname: stud.tSubname,
            }
            stud.sex = stud.sex === 'm' ? 'Masculin' : 'Feminin';
            const date = new Date(stud.birthday).getDate() + ' ' + months[new Date(stud.birthday).getMonth()] + " " + new Date(stud.birthday).getUTCFullYear()
            stud.birthday = date;

            req.connection.query('SELECT * FROM notes WHERE exam_id = ? AND class_id = ? AND student_id = ?', [exam_id, class_id, student_id], (err2, notes) => {
                if (err2) console.log(err2);
                notes.forEach(note => {
                    totalPoint += parseInt(note.value);
                });
                req.connection.query('SELECT * FROM matiere', (err3, mat: Matiere[]) => {
                    mat.forEach((m) => {
                        const tags = JSON.parse(m.tags);
                        const notesForThisMatiere = notes.filter(h => h.matiere_id === m.id);
                        const t = JSON.parse(m.tags).length + 2;
                        totalNote = 0;
                        let total = 0;
                        tags.map(tag => {
                            const notesForThisTag = notesForThisMatiere.filter((h: {
                                tag_name: string,
                            }) => h.tag_name === tag.name)[0];
                            const note = notesForThisTag && notesForThisTag !== undefined ? parseInt(notesForThisTag.value) : 0;
                            totalNote += note;
                            total += parseInt(tag.over);
                        })
                        if (totalNote < (total / 2)) {
                            badCompetence.push(m.name);
                        }
                    })

                    mat.forEach(m => {
                        const tags = JSON.parse(m.tags);
                        tags.forEach(tag => {
                            diviser += parseInt(tag.over);
                        })
                    })

                    req.connection.query('SELECT * FROM com', (err5, competences) => {
                        mat.map(m => {
                            let t = 0;
                            const tags = JSON.parse(m.tags);
                            m.tags = tags;
                            const notesForThisMatiere = notes.filter(h => h.matiere_id === m.id);
                            tags.forEach(tag => {
                                const notesForThisTag = notesForThisMatiere.filter(h => h.tag_name === tag.name)[0];
                                const note = notesForThisTag && notesForThisTag !== undefined ? parseInt(notesForThisTag.value) : 0;
                                t += note;
                                tag.value = note
                            })
                            m.t = tags.length + 2;
                            let o = 0;
                            tags.forEach(t => {
                                o += parseInt(t.over)
                            })
                            m.totalNote = o;
                            m.total = t;
                        })
                        competences.forEach(com => {
                            let to = 0;
                            com.sub = mat.filter(m => m.comId === com.id)
                            mat.filter(m => m.comId === com.id).forEach(m => {
                                const tags = m.tags;
                                to += tags.length + 2;
                            })
                            com.total = to + 1;
                        })
                        req.connection.query('SELECT * FROM stats WHERE class_id = ? AND exam_id = ? ', [class_id, exam_id], (errrt, stats) => {
                            const rangedArray = stats.sort((a, b) => b.totalPoints - a.totalPoints);
                            const g = stats.sort((a, b) => b.totalPoints - a.totalPoints);
                            let firstPoints: number = g[0].totalPoints;
                            let lastPoints: number = 0;
                            g.forEach((ey: {
                                totalPoints: number
                            }) => {
                                lastPoints = ey.totalPoints;
                            })
                            let rang = 0;
                            rangedArray.forEach((s: {
                                student_id: string
                            }, c) => {
                                if (s.student_id === student_id) {
                                    rang = c + 1
                                }
                            })
                            const document = {
                                html: html,
                                data: {
                                    student: stud,
                                    info: info,
                                    diviser: diviser,
                                    totalPoint,
                                    firstAverage: Math.round(((firstPoints / diviser) * 20) * 100) / 100,
                                    lastAverage: Math.round(((lastPoints / diviser) * 20) * 100) / 100,
                                    rang: rang,
                                    av: ((totalPoint / diviser) * 20) < 10 ? 'Oui' : 'Non',
                                    en: ((totalPoint / diviser) * 20) > 15 ? 'Oui' : 'Non',
                                    ho: ((totalPoint / diviser) * 20) > 18 ? 'Oui' : 'Non',
                                    moyenne: Math.round(((totalPoint / diviser) * 20) * 100) / 100,
                                    totalNote: totalNote,
                                    badCompetence: badCompetence,
                                    mat: mat,
                                    competences: competences,
                                    totalStudent: allStudents.length,
                                    notes: notes
                                },
                                path: `docs/${stud.name}.pdf`
                            };
                            pdf.create(document, optionsPdf)
                                .then(resp => {
                                    res.download(resp.filename)
                                })
                                .catch(err => {
                                    console.log(err);
                                    res.status(201).json({ err, f: document.data.competences })
                                })
                        })
                    })
                })
            })
        });
    })
}

module.exports.downloadBulletin5 = (req, res) => {
    const { exam_id, student_id, class_id } = req.params;
    const html = downloadFs.readFileSync('src/templates/Bulletin5.html', 'utf-8');
    let badCompetence: string[] = [];
    let totalPoint: number = 0;
    let totalNote: number;
    let to = 0;

    req.connection.query('SELECT * FROM students WHERE class_id = ?', [class_id], (errr, allStudents: []) => {
        req.connection.query("SELECT students.name, teachers.name as tName, teachers.subname as tSubname, students.subname, students.birthday, students.sex, class.name as cName  FROM students LEFT JOIN class ON class.id = students.class_id LEFT JOIN teachers ON teachers.class_id = class.id WHERE students.id = ?", [student_id], function (err, student, fields) {
            if (err) console.log(err);
            const stud: {
                name: string,
                subname: string,
                cName: string,
                tName: string,
                tSubname: string,
                sex: string,
                birthday: string,
            } = student[0];
            let info: {
                className: string,
                teacherName: string
                teacherSubname: string
            } = {
                className: stud.cName,
                teacherName: stud.tName,
                teacherSubname: stud.tSubname,
            }
            stud.sex = stud.sex === 'm' ? 'Masculin' : 'Feminin';
            const date = new Date(stud.birthday).getDate() + ' ' + months[new Date(stud.birthday).getMonth()] + " " + new Date(stud.birthday).getUTCFullYear()
            stud.birthday = date;

            req.connection.query('SELECT * FROM notesBySubject WHERE exam_id = ? AND class_id = ? AND student_id = ?', [exam_id, class_id, student_id], (err2, notes) => {
                if (err2) console.log(err2);
                notes.forEach(note => {
                    totalPoint += parseInt(note.value);
                });
                req.connection.query('SELECT subjects.name, subjects.id, subjects.over FROM subjects JOIN sections ON sections.id = subjects.section WHERE sections.type = 5', (err3, subjects) => {

                    req.connection.query('SELECT * FROM stats WHERE class_id = ? AND exam_id = ? ', [class_id, exam_id], (errrt, stats) => {
                        const rangedArray = stats.sort((a, b) => b.totalPoints - a.totalPoints);
                        const g = stats.sort((a, b) => b.totalPoints - a.totalPoints);
                        let firstPoints: number = g[0].totalPoints;
                        let lastPoints: number = 0;
                        g.forEach((ey: {
                            totalPoints: number
                        }) => {
                            lastPoints = ey.totalPoints;
                        })
                        let rang = 0;
                        rangedArray.forEach((s: {
                            student_id: string
                        }, c) => {
                            if (s.student_id === student_id) {
                                rang = c + 1
                            }
                        })
                        subjects.forEach(d => {
                            to += d.over;
                            const note = notes.filter(n => n.subject_id == d.id).length > 0 ? parseFloat(notes.filter(n => n.subject_id == d.id)[0].value) : 0
                            d.note = note;
                            if (d.note < d.over / 2) {
                                badCompetence.push(d.name)
                            }
                        })
                        const document = {
                            html: html,
                            data: {
                                student: stud,
                                info: info,
                                diviser: to,
                                totalPoint,
                                firstAverage: Math.round(((firstPoints / to) * 20) * 100) / 100,
                                lastAverage: Math.round(((lastPoints / to) * 20) * 100) / 100,
                                rang: rang,
                                av: ((totalPoint / to) * 20) < 10 ? 'Oui' : 'Non',
                                en: ((totalPoint / to) * 20) > 15 ? 'Oui' : 'Non',
                                ho: ((totalPoint / to) * 20) > 18 ? 'Oui' : 'Non',
                                moyenne: Math.round(((totalPoint / to) * 20) * 100) / 100,
                                totalNote: totalNote,
                                badCompetence: badCompetence,
                                subjects: subjects,
                                totalStudent: allStudents.length,
                                notes: notes
                            },
                            path: `docs/${stud.name}.pdf`
                        };
                        pdf.create(document, optionsPdf)
                            .then(resp => {
                                res.download(resp.filename)
                            })
                            .catch(err => {
                                console.log(err);
                                res.status(201).json({ err, f: document.data })
                            })
                    })
                })
            })
        });
    })
}

module.exports.downloadBulletin4 = (req, res) => {
    const { exam_id, student_id, class_id } = req.params;
    const html = downloadFs.readFileSync('src/templates/Bulletin4.html', 'utf-8');
    let badCompetence: string[] = [];
    let totalPoint: number = 0;
    let totalNote: number;
    let to = 0;

    req.connection.query('SELECT * FROM students WHERE class_id = ?', [class_id], (errr, allStudents: []) => {
        req.connection.query("SELECT students.name, teachers.name as tName, teachers.subname as tSubname, students.subname, students.birthday, students.sex, class.name as cName  FROM students LEFT JOIN class ON class.id = students.class_id LEFT JOIN teachers ON teachers.class_id = class.id WHERE students.id = ?", [student_id], function (err, student, fields) {
            if (err) console.log(err);
            const stud: {
                name: string,
                subname: string,
                cName: string,
                tName: string,
                tSubname: string,
                sex: string,
                birthday: string,
            } = student[0];
            let info: {
                className: string,
                teacherName: string
                teacherSubname: string
            } = {
                className: stud.cName,
                teacherName: stud.tName,
                teacherSubname: stud.tSubname,
            }
            stud.sex = stud.sex === 'm' ? 'Masculin' : 'Feminin';
            const date = new Date(stud.birthday).getDate() + ' ' + months[new Date(stud.birthday).getMonth()] + " " + new Date(stud.birthday).getUTCFullYear()
            stud.birthday = date;

            req.connection.query('SELECT * FROM notesBySubject WHERE exam_id = ? AND class_id = ? AND student_id = ?', [exam_id, class_id, student_id], (err2, notes) => {
                if (err2) console.log(err2);
                notes.forEach(note => {
                    totalPoint += parseInt(note.value);
                });
                req.connection.query('SELECT subjects.name, subjects.id, subjects.over FROM subjects JOIN sections ON sections.id = subjects.section WHERE sections.type = 4', (err3, subjects) => {

                    req.connection.query('SELECT * FROM stats WHERE class_id = ? AND exam_id = ? ', [class_id, exam_id], (errrt, stats) => {
                        const rangedArray = stats.sort((a, b) => b.totalPoints - a.totalPoints);
                        const g = stats.sort((a, b) => b.totalPoints - a.totalPoints);
                        let firstPoints: number = g[0].totalPoints;
                        let lastPoints: number = 0;
                        g.forEach((ey: {
                            totalPoints: number
                        }) => {
                            lastPoints = ey.totalPoints;
                        })
                        let rang = 0;
                        rangedArray.forEach((s: {
                            student_id: string
                        }, c) => {
                            if (s.student_id === student_id) {
                                rang = c + 1
                            }
                        })
                        subjects.forEach(d => {
                            to += d.over;
                            const note = notes.filter(n => n.subject_id == d.id).length > 0 ? parseFloat(notes.filter(n => n.subject_id == d.id)[0].value) : 0
                            d.note = note;
                            if (d.note < d.over / 2) {
                                badCompetence.push(d.name)
                            }
                        })
                        const document = {
                            html: html,
                            data: {
                                student: stud,
                                info: info,
                                diviser: to,
                                totalPoint,
                                firstAverage: Math.round(((firstPoints / to) * 20) * 100) / 100,
                                lastAverage: Math.round(((lastPoints / to) * 20) * 100) / 100,
                                rang: rang,
                                av: ((totalPoint / to) * 20) < 10 ? 'Oui' : 'Non',
                                en: ((totalPoint / to) * 20) > 15 ? 'Oui' : 'Non',
                                ho: ((totalPoint / to) * 20) > 18 ? 'Oui' : 'Non',
                                moyenne: Math.round(((totalPoint / to) * 20) * 100) / 100,
                                totalNote: totalNote,
                                badCompetence: badCompetence,
                                subjects: subjects,
                                totalStudent: allStudents.length,
                                notes: notes
                            },
                            path: `docs/${stud.name}.pdf`
                        };
                        pdf.create(document, optionsPdf)
                            .then(resp => {
                                res.download(resp.filename)
                            })
                            .catch(err => {
                                console.log(err);
                                res.status(201).json({ err, f: document.data })
                            })
                    })
                })
            })
        });
    })
}
module.exports.downloadBulletin2 = (req, res) => {
    const { exam_id, student_id, class_id } = req.params;
    const html = downloadFs.readFileSync('src/templates/Bulletin2.html', 'utf-8');
    let badCompetence: string[] = [];
    let totalNote: number;
    let to = 0;

    req.connection.query('SELECT * FROM students WHERE class_id = ?', [class_id], (errr, allStudents: []) => {
        req.connection.query("SELECT students.name, teachers.name as tName, teachers.subname as tSubname, students.subname, students.birthday, students.sex, class.name as cName  FROM students LEFT JOIN class ON class.id = students.class_id LEFT JOIN teachers ON teachers.class_id = class.id WHERE students.id = ?", [student_id], function (err, student, fields) {
            if (err) console.log(err);
            const stud: {
                name: string,
                subname: string,
                cName: string,
                tName: string,
                tSubname: string,
                sex: string,
                birthday: string,
            } = student[0];
            let info: {
                className: string,
                teacherName: string
                teacherSubname: string
            } = {
                className: stud.cName,
                teacherName: stud.tName,
                teacherSubname: stud.tSubname,
            }
            stud.sex = stud.sex === 'm' ? 'Masculin' : 'Feminin';
            const date = new Date(stud.birthday).getDate() + ' ' + months[new Date(stud.birthday).getMonth()] + " " + new Date(stud.birthday).getUTCFullYear()
            stud.birthday = date;

            req.connection.query('SELECT * FROM notesByDomain WHERE exam_id = ? AND class_id = ? AND student_id = ?', [exam_id, class_id, student_id], (err2, notes) => {
                if (err2) console.log(err2);
                req.connection.query('SELECT domains.name, domains.id, (SELECT COUNT(id) FROM activities WHERE activities.domainId = domains.id) as total_activities FROM domains JOIN sections ON sections.id = domains.section WHERE sections.type = 2', (err3, domains) => {
                    req.connection.query('SELECT activities.name, activities.appreciationsNber, activities.id, activities.domainId, activities.appreciationsNber as nber, sections.name as section_name FROM activities JOIN sections ON sections.id = activities.section', [], (a, activities) => {
                        domains.forEach(d => {
                            const act = activities.filter(acr => acr.domainId == d.id);
                            act.forEach(activitie => {
                                to += activitie.over;
                                const note: number = notes.filter(n => n.activitieId == activitie.id).length > 0 ? parseFloat(notes.filter(n => n.activitieId == activitie.id)[0].value) : 0
                                if (activitie.appreciationsNber == 4 && note != 2 || note != 1) {
                                    badCompetence.push(activitie.name)
                                }
                                else if (activitie.appreciationsNber == 3 && note !== 1) {
                                    badCompetence.push(activitie.name)
                                }
                                activitie.note = note;
                            });
                            d.total_activities = d.total_activities + 1;
                            d.activities = act;
                        })

                        const document = {
                            html: html,
                            data: {
                                student: stud,
                                info: info,
                                totalNote: totalNote,
                                badCompetence: badCompetence,
                                domains: domains,
                                totalStudent: allStudents.length,
                                notes: notes
                            },
                            path: `docs/${stud.name}.pdf`
                        };
                        pdf.create(document, optionsPdf)
                            .then(resp => {
                                res.download(resp.filename)
                            })
                            .catch(err => {
                                console.log(err);
                                res.status(201).json({ err, f: document.data })
                            })
                    })
                })
            })
        })
    })
}
module.exports.downloadBulletin1 = (req, res) => {
    const { exam_id, student_id, class_id } = req.params;
    const html = downloadFs.readFileSync('src/templates/Bulletin2.html', 'utf-8');
    let badCompetence: string[] = [];
    let totalNote: number;
    let to = 0;

    req.connection.query('SELECT * FROM students WHERE class_id = ?', [class_id], (errr, allStudents: []) => {
        req.connection.query("SELECT students.name, teachers.name as tName, teachers.subname as tSubname, students.subname, students.birthday, students.sex, class.name as cName  FROM students LEFT JOIN class ON class.id = students.class_id LEFT JOIN teachers ON teachers.class_id = class.id WHERE students.id = ?", [student_id], function (err, student, fields) {
            if (err) console.log(err);
            const stud: {
                name: string,
                subname: string,
                cName: string,
                tName: string,
                tSubname: string,
                sex: string,
                birthday: string,
            } = student[0];
            let info: {
                className: string,
                teacherName: string
                teacherSubname: string
            } = {
                className: stud.cName,
                teacherName: stud.tName,
                teacherSubname: stud.tSubname,
            }
            stud.sex = stud.sex === 'm' ? 'Masculin' : 'Feminin';
            const date = new Date(stud.birthday).getDate() + ' ' + months[new Date(stud.birthday).getMonth()] + " " + new Date(stud.birthday).getUTCFullYear()
            stud.birthday = date;

            req.connection.query('SELECT * FROM notesByDomain WHERE exam_id = ? AND class_id = ? AND student_id = ?', [exam_id, class_id, student_id], (err2, notes) => {
                if (err2) console.log(err2);
                req.connection.query('SELECT domains.name, domains.id, (SELECT COUNT(id) FROM activities WHERE activities.domainId = domains.id) as total_activities FROM domains JOIN sections ON sections.id = domains.section WHERE sections.type = 1', (err3, domains) => {
                    req.connection.query('SELECT activities.name, activities.appreciationsNber, activities.id, activities.domainId, activities.appreciationsNber as nber, sections.name as section_name FROM activities JOIN sections ON sections.id = activities.section', [], (a, activities) => {
                        domains.forEach(d => {
                            const act = activities.filter(acr => acr.domainId == d.id);
                            act.forEach(activitie => {
                                to += activitie.over;
                                const note: number = notes.filter(n => n.activitieId == activitie.id).length > 0 ? parseFloat(notes.filter(n => n.activitieId == activitie.id)[0].value) : 0
                                if (activitie.appreciationsNber == 4 && note != 2 || note != 1) {
                                    badCompetence.push(activitie.name)
                                }
                                else if (activitie.appreciationsNber == 3 && note !== 1) {
                                    badCompetence.push(activitie.name)
                                }
                                activitie.note = note;
                            });
                            d.total_activities = d.total_activities + 1;
                            d.activities = act;
                        })

                        const document = {
                            html: html,
                            data: {
                                student: stud,
                                info: info,
                                totalNote: totalNote,
                                badCompetence: badCompetence,
                                domains: domains,
                                totalStudent: allStudents.length,
                                notes: notes
                            },
                            path: `docs/${stud.name}.pdf`
                        };
                        pdf.create(document, optionsPdf)
                            .then(resp => {
                                res.download(resp.filename)
                            })
                            .catch(err => {
                                console.log(err);
                                res.status(201).json({ err, f: document.data })
                            })
                    })
                })
            })
        })
    })
}

module.exports.downloadBulletinByClass = async (req, res) => {
    const zip = new admZip();
    const { class_id } = req.params;
    req.connection.query('SELECT students.id, students.name, teachers.name as tName, teachers.subname as tSubname, students.subname, students.birthday, students.sex, class.name as cName  FROM students LEFT JOIN class ON class.id = students.class_id LEFT JOIN teachers ON teachers.class_id = class.id WHERE students.class_id = ?', [class_id], async (err, students) => {
        const dirPath = `docs/${students[0].cName}`;
        if (!downloadFs.existsSync(dirPath)) downloadFs.mkdirSync(dirPath);
        await new Promise((resolve, reject) => {
            students.forEach(tt => {
                const { exam_id, class_id } = req.params;
                const html = downloadFs.readFileSync('src/templates/Bulletin.html', 'utf-8');
                let badCompetence: string[] = [];
                let totalPoint = 0;
                let totalNote: number
                let diviser: number = 0;
                const stud = tt;
                let info: {
                    className: string,
                    teacherName: string
                    teacherSubname: string
                } = {
                    className: stud.cName,
                    teacherName: stud.tName,
                    teacherSubname: stud.tSubname,
                }
                stud.sex = stud.sex === 'm' ? 'Masculin' : 'Feminin';
                const date = new Date(stud.birthday).getDate() + ' ' + months[new Date(stud.birthday).getMonth()] + " " + new Date(stud.birthday).getUTCFullYear()
                stud.birthday = date;

                req.connection.query('SELECT * FROM notes WHERE exam_id = ? AND class_id = ? AND student_id = ?', [exam_id, class_id, stud.id], (err2, notes) => {
                    if (err2) console.log(err2);
                    notes.forEach((note: {
                        value: string
                    }) => {
                        totalPoint += parseInt(note.value);
                    });
                    req.connection.query('SELECT * FROM matiere', (err3, mat: Matiere[]) => {
                        mat.forEach(m => {
                            const tags = JSON.parse(m.tags);
                            const notesForThisMatiere = notes.filter(h => h.matiere_id === m.id);
                            const t: number = JSON.parse(m.tags).length + 2;
                            totalNote = 0;
                            let total = 0;
                            tags.map(tag => {
                                const notesForThisTag = notesForThisMatiere.filter(h => h.tag_name === tag.name)[0];
                                const note = notesForThisTag && notesForThisTag !== undefined ? parseInt(notesForThisTag.value) : 0;
                                totalNote += note;
                                total += parseInt(tag.over);
                            })
                            if (totalNote < (total / 2)) {
                                badCompetence.push(m.name);
                            }
                        })

                        mat.forEach(m => {
                            const tags = JSON.parse(m.tags);
                            tags.forEach(tag => {
                                diviser += parseInt(tag.over);
                            })
                        })

                        req.connection.query('SELECT * FROM com', (err5, competences) => {
                            mat.map((m) => {
                                let t = 0;
                                const tags = JSON.parse(m.tags);
                                m.tags = tags;
                                const notesForThisMatiere = notes.filter(h => h.matiere_id === m.id);
                                tags.forEach(tag => {
                                    const notesForThisTag = notesForThisMatiere.filter(h => h.tag_name === tag.name)[0];
                                    const note = notesForThisTag && notesForThisTag !== undefined ? parseInt(notesForThisTag.value) : 0;
                                    t += note;
                                    tag.value = note
                                })
                                m.t = tags.length + 2;
                                let o = 0;
                                tags.forEach(t => {
                                    o += parseInt(t.over)
                                })
                                m.totalNote = o;
                                m.total = t;
                            })
                            competences.forEach(com => {
                                let to = 0;
                                com.sub = mat.filter(m => m.comId === com.id)
                                mat.filter(m => m.comId === com.id).forEach(m => {
                                    const tags = m.tags;
                                    to += tags.length + 2;
                                })
                                com.total = to + 1;
                            })
                            req.connection.query('SELECT * FROM stats WHERE class_id = ? AND exam_id = ? ', [class_id, exam_id], (errrt, stats) => {
                                const rangedArray = stats.sort((a, b) => b.totalPoints - a.totalPoints);
                                const g = stats.sort((a, b) => b.totalPoints - a.totalPoints);
                                let firstPoints: number = g[0].totalPoints;
                                let lastPoints: number = 0;
                                g.forEach(ey => {
                                    lastPoints = ey.totalPoints;
                                })
                                let rang = 0;
                                rangedArray.forEach((s: {
                                    student_id: string
                                }, c) => {
                                    if (s.student_id === stud.id) {
                                        rang = c + 1
                                    }
                                })
                                const document = {
                                    html: html,
                                    data: {
                                        student: stud,
                                        info: info,
                                        diviser: diviser,
                                        totalPoint,
                                        firstAverage: Math.round(((firstPoints / diviser) * 20) * 100) / 100,
                                        lastAverage: Math.round(((lastPoints / diviser) * 20) * 100) / 100,
                                        rang: rang,
                                        av: ((totalPoint / diviser) * 20) < 10 ? 'Oui' : 'Non',
                                        en: ((totalPoint / diviser) * 20) > 15 ? 'Oui' : 'Non',
                                        ho: ((totalPoint / diviser) * 20) > 18 ? 'Oui' : 'Non',
                                        moyenne: Math.round(((totalPoint / diviser) * 20) * 100) / 100,
                                        totalNote: totalNote,
                                        badCompetence: badCompetence,
                                        mat: mat,
                                        competences: competences,
                                        totalStudent: students.length,
                                        notes: notes
                                    },
                                    path: `${dirPath}/${(stud.name + ' ' + stud.subname).replaceAll(' ', '_')}.pdf`
                                };
                                pdf.create(document, optionsPdf)
                                    .then(resp => {

                                    })
                                    .catch(err => {
                                        console.log(err);
                                        res.status(201).json({ err, f: document.data.competences })
                                    })
                            })
                        })
                    })
                })
            });

            resolve({})
        })

        setTimeout(() => {
            students.forEach(student => {
                zip.addLocalFile(`${dirPath}/${(student.name + ' ' + student.subname).replaceAll(' ', '_')}.pdf`);
                const zipPath = `${dirPath}/Bulletins en ${students[0].cName}.zip`;
                downloadFs.writeFileSync(zipPath, zip.toBuffer());
                res.download(zipPath);
            })
        }, 3000)
    })
}

module.exports.downloadRecu = (req: any, res: any) => {
    const {student_id, amount, payload, diff} = req.params;
    req.connection.query('SELECT * FROM students WHERE id = ?', [student_id], (err, students) => {
        const student = students[0];
        req.connection.query('SELECT * FROM class WHERE id = ?', [student.class_id], (err2, classes) => {
            
            const t = [];
            for (let i = 0; i < 4; i++) {
              const i = Math.round((Math.random() * 10));
              t.push(i);
            }
            const recu_name = t.join('');
            const trust_payload = req.jwt.verify(payload, req.env.SECRET);
            JSON.parse(diff).forEach(ta => {
                if (ta.value > 0) {
                    req.connection.query('INSERT INTO payments_details(amount, recu_name, student_id, operator_id, tag) VALUES(?, ?, ?, ?, ?)',
                                    [ta.value, recu_name, student_id, trust_payload.id, ta.name], 
                                    (err3, comptable) => {

                                    })
                }
            });
            req.connection.query('INSERT INTO payments(amount, recu_name, student_id, operator_id) VALUES(?, ?, ?, ?)', 
                                    [amount, recu_name, student_id, trust_payload.id], 
                                    (err3, comptable) => {
                req.connection.query('SELECT * FROM payments WHERE recu_name = ?', [recu_name], (ee, payments) => {
                    const classe = classes[0];
                    
                    const payment = payments[0];
                    const html = downloadFs.readFileSync('src/templates/Recu.html', 'utf-8');
                    const fileName = `Reçu de ${student.name} ${student.subname} ${new Date().getFullYear()}-${new Date().getMonth()}-${new Date().getDate()}.pdf`;

                    const inscription = student.status === 'old' ? classe.inscriptions_olds_students : classe.inscriptions_news_students
                    const first_tranch = student.status === 'old' ? classe.first_tranch_olds_students : classe.first_tranch_news_students
                    const second_tranch = student.status === 'old' ? classe.second_tranch_olds_students : classe.second_tranch_news_students;
                    const third_tranch = student.status === 'old' ? classe.third_tranch_olds_students : classe.third_tranch_news_students;
                    const graduation = classe.graduation ?  classe.graduation : 0;
                    const total = inscription + first_tranch + second_tranch + third_tranch + graduation + 3000;
                    const totalPayed = student.inscription + student.first_tranch + student.second_tranch + student.third_tranch + student.graduation + student.assurance;

                    const inscription_rest = inscription - student.inscription;
                    const first_tranch_rest = first_tranch - student.first_tranch;
                    const second_tranch_rest = second_tranch - student.second_tranch;
                    const third_tranch_rest = third_tranch - student.third_tranch;
                    const graduation_rest = classe.graduation - student.graduation;

                    req.connection.query('SELECT * FROM users WHERE id = ?', [trust_payload.id], (eee, users) => {
                        const user = users[0];
                        const username = user.email.toUpperCase();                        

                        const document = {
                            html: html,
                            data: {
                                payment,
                                student,
                                classe,
                                amount,
                                inscription,
                                school_year: new Date().getFullYear(),
                                next_school_year: new Date().getFullYear() + 1,
                                date : new Date().getDate() + '/' + (new Date().getMonth() + 1) + '/' + new Date().getFullYear() + ' à ' + new Date().getHours()  + ':' + new Date().getMinutes() ,
                                username,
                                first_tranch,
                                second_tranch,
                                third_tranch,
                                inscription_rest,
                                first_tranch_rest,
                                graduation_rest,
                                second_tranch_rest,
                                assurance_rest: 3000 - student.assurance,
                                third_tranch_rest,
                                totalFees: first_tranch + second_tranch + third_tranch,
                                total,
                                totalPayed,
                                difference: total - totalPayed
                            },
                            path: `docs/${fileName}`
                        };
                        pdf.create(document, optionsPdfRecu)
                            .then(resp => {
                                var data = downloadFs.readFileSync('docs/'+fileName);
                                res.contentType("application/pdf");
                                res.send(data);
                            })
                            .catch(err => {
                                console.log(err);
                                res.status(201).json(err)
                            })
                    })
                })
            })
        })
    })
}
module.exports.downloadRecu2 = (req: any, res: any) => {
    const {student_id, amount, payload, diff} = req.params;
    req.connection.query('SELECT * FROM students WHERE id = ?', [student_id], (err, students) => {
        const student = students[0];
        req.connection.query('SELECT * FROM class WHERE id = ?', [student.class_id], (err2, classes) => {
            
            const t = [];
            for (let i = 0; i < 4; i++) {
              const i = Math.round((Math.random() * 10));
              t.push(i);
            }
            const recu_name = t.join('');
            const trust_payload = req.jwt.verify(payload, req.env.SECRET);
            
            JSON.parse(diff).forEach(ta => {
                if (ta.value > 0) {
                    req.connection.query('INSERT INTO payments_details(amount, recu_name, student_id, operator_id, tag) VALUES(?, ?, ?, ?, ?)',
                                    [ta.value, recu_name, student_id, trust_payload.id, ta.name], 
                                    (err3, comptable) => {

                                    })
                }
            });
            req.connection.query('INSERT INTO payments(amount, recu_name, student_id, operator_id) VALUES(?, ?, ?, ?)', 
                                    [amount, recu_name, student_id, trust_payload.id], 
                                    (err3, comptable) => {
                req.connection.query('SELECT * FROM payments WHERE recu_name = ?', [recu_name], (ee, payments) => {
                    const classe = classes[0];
                    
                    const payment = payments[0];
                    const html = downloadFs.readFileSync('src/templates/Recu.html', 'utf-8');
                    const fileName = `Reçu de ${student.name} ${student.subname} ${new Date().getFullYear()}-${new Date().getMonth()}-${new Date().getDate()}.pdf`;

                    const inscription = student.status === 'old' ? classe.inscriptions_olds_students : classe.inscriptions_news_students
                    const first_tranch = student.status === 'old' ? classe.first_tranch_olds_students : classe.first_tranch_news_students
                    const second_tranch = student.status === 'old' ? classe.second_tranch_olds_students : classe.second_tranch_news_students;
                    const third_tranch = student.status === 'old' ? classe.third_tranch_olds_students : classe.third_tranch_news_students;
                    const graduation = classe.graduation ?  classe.graduation : 0;
                    const total = inscription + first_tranch + second_tranch + third_tranch + graduation + 3000;
                    const totalPayed = student.inscription + student.first_tranch + student.second_tranch + student.third_tranch + student.graduation + student.assurance;

                    const inscription_rest = inscription - student.inscription;
                    const first_tranch_rest = first_tranch - student.first_tranch;
                    const second_tranch_rest = second_tranch - student.second_tranch;
                    const third_tranch_rest = third_tranch - student.third_tranch;
                    const graduation_rest = classe.graduation - student.graduation;

                    req.connection.query('SELECT * FROM users WHERE id = ?', [trust_payload.id], (eee, users) => {
                        const user = users[0];
                        const username = user.email.toUpperCase();                        

                        const document = {
                            html: html,
                            data: {
                                payment,
                                student,
                                classe,
                                amount,
                                inscription,
                                school_year: new Date().getFullYear(),
                                next_school_year: new Date().getFullYear() + 1,
                                date : new Date().getDate() + '/' + (new Date().getMonth() + 1) + '/' + new Date().getFullYear() + ' à ' + new Date().getHours()  + ':' + new Date().getMinutes() ,
                                username,
                                first_tranch,
                                second_tranch,
                                third_tranch,
                                inscription_rest,
                                first_tranch_rest,
                                graduation_rest,
                                second_tranch_rest,
                                assurance_rest: 3000 - student.assurance,
                                third_tranch_rest,
                                totalFees: first_tranch + second_tranch + third_tranch,
                                total,
                                totalPayed,
                                difference: total - totalPayed
                            },
                            path: `docs/${fileName}`
                        };
                        pdf.create(document, optionsPdfRecu)
                            .then(resp => {
                                var data = downloadFs.readFileSync('docs/'+fileName);
                                res.contentType("application/pdf");
                                res.send(data);
                            })
                            .catch(err => {
                                console.log(err);
                                res.status(201).json(err)
                            })
                    })
                })
            })
        })
    })
}
module.exports.downloadInsolvablesList = (req, res) => {
    const {type, payload} = req.params;
    let fileToRead = 'insolvables';
    req.connection.query(`SELECT s.assurance, s.status, s.inscription, s.first_tranch,
                            s.second_tranch, s.third_tranch, s.graduation, 
                            c.inscriptions_olds_students, c.inscriptions_news_students,
                            c.first_tranch_olds_students , c.first_tranch_news_students,
                            c.second_tranch_olds_students , c.second_tranch_news_students,
                            c.third_tranch_olds_students , c.third_tranch_news_students,
                            c.graduation as c_g
                            FROM students s JOIN class c ON s.class_id = c.id`, 
                        [], (e, ss) => {
        req.connection.query(`SELECT DISTINCT * FROM students`, 
                           function (err, students, fields) {
            req.connection.query('SELECT * FROM students WHERE class_id = ? AND is_new = "no" ORDER BY name ASC', 
                            [req.params.id] , (err, oldStudents) => {

                if(err) console.log(err);
                else {
                    req.connection.query('SELECT * FROM students WHERE class_id = ? AND is_new = "yes"',
                            [req.params.id] , (err, newStudents) => {
                        let tt = [];
                        if (newStudents.length > 0) {
                            tt = [...oldStudents, ...newStudents]
                        }
                        else{
                            tt = [...oldStudents]
                        }
                        if (err) console.log(err);
                        const trust_payload = req.jwt.verify(payload, req.env.SECRET)
                        req.connection.query('SELECT * FROM users WHERE id = ?', [trust_payload.id], (er5, users) => {
                            const user = users[0];
                            req.connection.query('SELECT * FROM class WHERE id = ?', [req.params.id], (e, classes) => {
                                const classe = classes[0];
                                students = students.filter(s => s.class_id === req.params.id);
                                        
                                let students2 = new Set();
                                let global = {
                                    payed: {
                                        ass: 0,
                                        ins: 0,
                                        ftr: 0,
                                        str: 0,
                                        ttr: 0,
                                        gra: 0,
                                        general: 0
                                    },
                                    avanced: {
                                        ass: 0,
                                        ins: 0,
                                        ftr: 0,
                                        str: 0,
                                        ttr: 0,
                                        gra: 0,
                                        general: 0
                                    },
                                    nothing: {
                                        ass: 0,
                                        ins: 0,
                                        ftr: 0,
                                        str: 0,
                                        ttr: 0,
                                        gra: 0,
                                        general: 0
                                    },
                                    total: {
                                        inscription: 0,
                                        first_tranch: 0,
                                        second_tranch: 0,
                                        third_tranch: 0,
                                        graduation: 0,
                                        assurance: 0,
                                        general: 0
                                    },
                                    over: {
                                        inscription: 0,
                                        first_tranch: 0,
                                        second_tranch: 0,
                                        third_tranch: 0,
                                        graduation: 0,
                                        assurance: 0,
                                        general: 0
                                    }
                                };
                                let global_by_class = {
                                    payed: {
                                        ass: 0,
                                        ins: 0,
                                        ftr: 0,
                                        str: 0,
                                        ttr: 0,
                                        gra: 0,
                                        general: 0
                                    },
                                    avanced: {
                                        ass: 0,
                                        ins: 0,
                                        ftr: 0,
                                        str: 0,
                                        ttr: 0,
                                        gra: 0,
                                        general: 0
                                    },
                                    nothing: {
                                        ass: 0,
                                        ins: 0,
                                        ftr: 0,
                                        str: 0,
                                        ttr: 0,
                                        gra: 0,
                                        general: 0
                                    },
                                    total: {
                                        inscription: 0,
                                        first_tranch: 0,
                                        second_tranch: 0,
                                        third_tranch: 0,
                                        graduation: 0,
                                        assurance: 0,
                                        general: 0
                                    },
                                    over: {
                                        inscription: 0,
                                        first_tranch: 0,
                                        second_tranch: 0,
                                        third_tranch: 0,
                                        graduation: 0,
                                        assurance: 0,
                                        general: 0
                                    }
                                };
                                ss.forEach(student => {
                                    const inscription = student.status === 'old' ? student.inscriptions_olds_students : student.inscriptions_news_students
                                    const first_tranch = student.status === 'old' ? student.first_tranch_olds_students : student.first_tranch_news_students
                                    const second_tranch = student.status === 'old' ? student.second_tranch_olds_students : student.second_tranch_news_students;
                                    const third_tranch = student.status === 'old' ? student.third_tranch_olds_students : student.third_tranch_news_students;
                                    const graduation = student.graduation ? student.graduation : 0;

                                    global.over.assurance += 3000;
                                    global.over.inscription += inscription;
                                    global.over.first_tranch += first_tranch;
                                    global.over.second_tranch += second_tranch;
                                    global.over.third_tranch += third_tranch;
                                    global.over.graduation += graduation;

                                    const restToPay = (inscription + first_tranch + second_tranch + third_tranch + graduation + 3000) - (student.inscription + student.first_tranch + student.assurance + student.second_tranch + student.third_tranch + student.graduation);
                                    const totalPayed = (student.inscription + student.first_tranch + student.second_tranch + student.third_tranch + student.graduation + student.assurance);

                                    if (student.inscription == 0) {
                                        global.nothing.ins += 1
                                    }
                                    else if (student.inscription < inscription ) {
                                        global.avanced.ins += 1
                                    }
                                    else {
                                        global.payed.ins += 1
                                    }

                                    if (student.first_tranch == 0) {
                                        global.nothing.ftr += 1
                                    }
                                    else if (student.first_tranch < first_tranch ) {
                                        global.avanced.ftr += 1
                                    }
                                    else {
                                        global.payed.ftr += 1
                                    }                    
                                    
                                    if (student.second_tranch == 0) {
                                        global.nothing.str += 1
                                    }
                                    else if (student.second_tranch < second_tranch ) {
                                        global.avanced.str += 1
                                    }
                                    else {
                                        global.payed.str += 1
                                    }

                                    if (student.third_tranch == 0) {
                                        global.nothing.ttr += 1
                                    }
                                    else if (student.third_tranch < third_tranch ) {
                                        global.avanced.ttr += 1
                                    }
                                    else {
                                        global.payed.ttr += 1
                                    }

                                    if (student.graduation == 0) {
                                        global.nothing.gra += 1
                                    }
                                    else if (student.graduation < graduation ) {
                                        global.avanced.gra += 1
                                    }
                                    else {
                                        global.payed.gra += 1
                                    }

                                    if (student.assurance == 0) {
                                        global.nothing.ass += 1
                                    }
                                    else if (student.assurance < 3000 ) {
                                        global.avanced.ass += 1
                                    }
                                    else {
                                        global.payed.ass += 1
                                    }

                                    if (totalPayed == 0) {
                                        global.nothing.general += 1
                                    }
                                    else if (restToPay > 0) {
                                        global.avanced.general += 1
                                    }
                                    else {
                                        global.payed.general += 1
                                    }
                                    global.total.assurance += student.assurance;
                                    global.total.inscription += student.inscription;
                                    global.total.first_tranch += student.first_tranch;
                                    global.total.second_tranch += student.second_tranch;
                                    global.total.third_tranch += student.third_tranch;
                                    global.total.graduation += student.graduation;
                                    
                                });
                                global.over.general = global.over.assurance + global.over.inscription + global.over.first_tranch + global.over.second_tranch + global.over.third_tranch + global.over.graduation
                                global.total.general = global.total.assurance + global.total.inscription + global.total.first_tranch + global.total.second_tranch + global.total.third_tranch + global.total.graduation
                                let name = '';
                                let info: {
                                    className: string,
                                    operator: string,
                                    graduation: number
                                } = {
                                    className: classe.name,
                                    operator: user.email,
                                    graduation: classe.graduation ? 1 : 0
                                }
                                let fileName = `Liste des insolvables de ${info.className}.pdf`;
                                const total = {
                                    inscription: 0,
                                    first_tranch: 0,
                                    second_tranch: 0,
                                    third_tranch: 0,
                                    graduation: 0,
                                    assurance: 0,
                                    general: 0
                                }
                                tt.forEach((student, idsd) => {
                                    student.number = idsd + 1;
                                    const inscription = student.status === 'old' ? classe.inscriptions_olds_students : classe.inscriptions_news_students
                                    const first_tranch = student.status === 'old' ? classe.first_tranch_olds_students : classe.first_tranch_news_students
                                    const second_tranch = student.status === 'old' ? classe.second_tranch_olds_students : classe.second_tranch_news_students;
                                    const third_tranch = student.status === 'old' ? classe.third_tranch_olds_students : classe.third_tranch_news_students;
                                    const graduation = classe.graduation ? classe.graduation : 0;
                    
                                    
                                    global_by_class.over.assurance += 3000;
                                    global_by_class.over.inscription += inscription;
                                    global_by_class.over.first_tranch += first_tranch;
                                    global_by_class.over.second_tranch += second_tranch;
                                    global_by_class.over.third_tranch += third_tranch;
                                    global_by_class.over.graduation += graduation;

                                    const inscription_rest = inscription - student.inscription;
                                    const first_tranch_rest = first_tranch - student.first_tranch;
                                    const second_tranch_rest = second_tranch - student.second_tranch;
                                    const third_tranch_rest = third_tranch - student.third_tranch;
                                    const graduation_rest = graduation - student.graduation;
                                    const assurance_rest = 3000 - student.assurance;
                    
                                    const restToPay = (inscription + first_tranch + second_tranch + third_tranch + graduation + 3000) - (student.inscription + student.first_tranch + student.assurance + student.second_tranch + student.third_tranch + student.graduation);
                                    const totalToPay = inscription + first_tranch + second_tranch + third_tranch + graduation + 3000;
                                    const totalPayed = (student.inscription + student.first_tranch + student.second_tranch + student.third_tranch + student.graduation + student.assurance);
                                    total.inscription += student.inscription;
                                    total.first_tranch += student.first_tranch;
                                    total.second_tranch += student.second_tranch;
                                    total.third_tranch += student.third_tranch;
                                    total.graduation += student.graduation;
                                    total.assurance += student.assurance;
                                    total.general += totalPayed

                                    
                                    if (student.inscription == 0) {
                                        global_by_class.nothing.ins += 1
                                    }
                                    else if (student.inscription < inscription ) {
                                        global_by_class.avanced.ins += 1
                                    }
                                    else {
                                        global_by_class.payed.ins += 1
                                    }

                                    if (student.first_tranch == 0) {
                                        global_by_class.nothing.ftr += 1
                                    }
                                    else if (student.first_tranch < first_tranch ) {
                                        global_by_class.avanced.ftr += 1
                                    }
                                    else {
                                        global_by_class.payed.ftr += 1
                                    }                    
                                    
                                    if (student.second_tranch == 0) {
                                        global_by_class.nothing.str += 1
                                    }
                                    else if (student.second_tranch < second_tranch ) {
                                        global_by_class.avanced.str += 1
                                    }
                                    else {
                                        global_by_class.payed.str += 1
                                    }

                                    if (student.third_tranch == 0) {
                                        global_by_class.nothing.ttr += 1
                                    }
                                    else if (student.third_tranch < third_tranch ) {
                                        global_by_class.avanced.ttr += 1
                                    }
                                    else {
                                        global_by_class.payed.ttr += 1
                                    }

                                    if (student.graduation == 0) {
                                        global_by_class.nothing.gra += 1
                                    }
                                    else if (student.graduation < graduation ) {
                                        global_by_class.avanced.gra += 1
                                    }
                                    else {
                                        global_by_class.payed.gra += 1
                                    }

                                    if (student.assurance == 0) {
                                        global_by_class.nothing.ass += 1
                                    }
                                    else if (student.assurance < 3000 ) {
                                        global_by_class.avanced.ass += 1
                                    }
                                    else {
                                        global_by_class.payed.ass += 1
                                    }

                                    if (totalPayed == 0) {
                                        global_by_class.nothing.general += 1
                                    }
                                    else if (restToPay > 0) {
                                        global_by_class.avanced.general += 1
                                    }
                                    else {
                                        global_by_class.payed.general += 1
                                    }
                                    switch (type) {
                                        case '1':
                                            if (inscription_rest >= 0) {
                                                student.rest = inscription_rest;
                                                students2.add(student);
                                                fileName = `Inscriptions en ${info.className}.pdf`;
                                            }
                                            name = 'l\'inscription';
                                            break;
                                    
                                        case '2':
                                            if (first_tranch_rest >= 0) {
                                                student.rest = first_tranch_rest;
                                                students2.add(student);
                                                fileName = `Premiere tranche en ${info.className}.pdf`;
                                            }
                                            name = 'la première tranche';
                                            break;
                    
                                        case '3':
                                            if (second_tranch_rest >= 0) {
                                                student.rest = second_tranch_rest;
                                                students2.add(student);
                                                fileName = `Seconde tranche en ${info.className}.pdf`;
                                            }
                                            name = 'la deuxième tranche';
                                            break;
                    
                                        case '4':
                                            if (third_tranch_rest >= 0) {
                                                student.rest = third_tranch_rest;
                                                students2.add(student);
                                            }
                                            name = 'la troisième tranche';
                                            fileName = `Troisieme tranche en ${info.className}.pdf`;
                                            break;
                    
                                        case '5':
                                            if (graduation_rest >= 0) {
                                                student.rest = graduation_rest;
                                                students2.add(student);
                                            }
                                            name = 'la graduation';
                                            fileName = `Graduation en ${info.className}.pdf`;
                                            break;
                    
                                        case '8':
                                            if (assurance_rest >= 0) {
                                                student.rest = assurance_rest;
                                                students2.add(student);
                                            }
                                            name = 'l\'assurance sante';
                                            fileName = `Assurance sante en ${info.className}.pdf`;
                                            break;
                    
                                        case '6':
                                            fileToRead = 'insolvablesTotal';
                                            student.inscription_rest = inscription_rest;
                                            student.first_tranch_rest = first_tranch_rest;
                                            student.second_tranch_rest = second_tranch_rest;
                                            student.third_tranch_rest = third_tranch_rest;
                                            student.graduation_rest = graduation_rest;
                                            student.assurance_rest = assurance_rest;
                                            student.restToPay = restToPay;
                                            
                                            student.inscription_scolarity = inscription;
                                            student.first_tranch_scolarity = first_tranch;
                                            student.second_tranch_scolarity = second_tranch;
                                            student.third_tranch_scolarity = third_tranch;
                                            student.graduation_scolarity = graduation > 0 ? graduation : null;
                                            student.totalToPay = totalToPay;
                    
                                            students2 = students;
                                            fileName = `Insolvables en ${info.className}.pdf`;
                                            name = 'tout';
                                            break;
                    
                                        case '7':
                                            fileToRead = 'insolvablesTotal2';
                                            student.totalPayed = totalPayed;
                                            student.inscription_scolarity = inscription;
                                            student.first_tranch_scolarity = first_tranch;
                                            student.second_tranch_scolarity = second_tranch;
                                            student.third_tranch_scolarity = third_tranch;
                                            student.graduation_scolarity = graduation > 0 ? graduation : null;
                                            student.totalToPay = totalToPay;
                    
                                            students2 = students;
                                            fileName = `Etat de la scolarite en ${info.className}.pdf`;
                                            name = 'tout';
                                            break;
                                        default:
                                            break;
                                    }
                                }); 
                                global_by_class.over.general = global_by_class.over.assurance + global_by_class.over.inscription + global_by_class.over.first_tranch + global_by_class.over.second_tranch + global_by_class.over.third_tranch + global_by_class.over.graduation
                                
                                const html = downloadFs.readFileSync(`src/templates/${fileToRead}.html`, 'utf-8');
                                const document = {
                                    html: html,
                                    data: {
                                        graduation: classe.graduation,
                                        students: [...new Set(tt)],
                                        info,
                                        name,
                                        classe,
                                        total,
                                        global,
                                        global_by_class
                                    },
                                    path: `docs/${fileName}`
                                };
                                pdf.create(document, optionsPdf)
                                    .then(() => {
                                        res.download(`docs/${fileName}`)
                                    })
                                    .catch(err => {
                                        console.log(err);
                                        
                                        res.status(201).json(err)
                                    })
                            })
                        })
                    })
                }
            })
        });
    })
}
module.exports.downloadRecette = (req, res) => {
    let global = {
        payed: {
            ass: 0,
            ins: 0,
            ftr: 0,
            str: 0,
            ttr: 0,
            gra: 0,
            general: 0
        },
        avanced: {
            ass: 0,
            ins: 0,
            ftr: 0,
            str: 0,
            ttr: 0,
            gra: 0,
            general: 0
        },
        nothing: {
            ass: 0,
            ins: 0,
            ftr: 0,
            str: 0,
            ttr: 0,
            gra: 0,
            general: 0
        },
        total: {
            inscription: 0,
            first_tranch: 0,
            second_tranch: 0,
            third_tranch: 0,
            graduation: 0,
            assurance: 0,
            general: 0
        },
        over: {
            inscription: 0,
            first_tranch: 0,
            second_tranch: 0,
            third_tranch: 0,
            graduation: 0,
            assurance: 0,
            general: 0
        }
    };
    req.connection.query(`SELECT s.assurance, s.status, s.inscription, s.first_tranch,
                            s.second_tranch, s.third_tranch, s.graduation, 
                            c.inscriptions_olds_students, c.inscriptions_news_students,
                            c.first_tranch_olds_students , c.first_tranch_news_students,
                            c.second_tranch_olds_students , c.second_tranch_news_students,
                            c.third_tranch_olds_students , c.third_tranch_news_students,
                            c.graduation as c_g
                            FROM students s JOIN class c ON s.class_id = c.id`, [], (e, stu) => {
        if (e) console.log(e);
        

        stu.forEach(student => {

            const inscription = student.status === 'old' ? student.inscriptions_olds_students : student.inscriptions_news_students
            const first_tranch = student.status === 'old' ? student.first_tranch_olds_students : student.first_tranch_news_students
            const second_tranch = student.status === 'old' ? student.second_tranch_olds_students : student.second_tranch_news_students;
            const third_tranch = student.status === 'old' ? student.third_tranch_olds_students : student.third_tranch_news_students;
            const graduation = student.c_g ? student.c_g : 0;

            
            global.over.assurance += 3000;
            global.over.inscription += inscription;
            global.over.first_tranch += first_tranch;
            global.over.second_tranch += second_tranch;
            global.over.third_tranch += third_tranch;
            global.over.graduation += graduation;

            const restToPay = (inscription + first_tranch + second_tranch + third_tranch + graduation + 3000) - (student.inscription + student.first_tranch + student.assurance + student.second_tranch + student.third_tranch + student.graduation);
            const totalPayed = (student.inscription + student.first_tranch + student.second_tranch + student.third_tranch + student.graduation + student.assurance);

            if (student.inscription == 0) {
                global.nothing.ins += 1
            }
            else if (student.inscription < inscription ) {
                global.avanced.ins += 1
            }
            else {
                global.payed.ins += 1
            }

            if (student.first_tranch == 0) {
                global.nothing.ftr += 1
            }
            else if (student.first_tranch < first_tranch ) {
                global.avanced.ftr += 1
            }
            else {
                global.payed.ftr += 1
            }                    
            
            if (student.second_tranch == 0) {
                global.nothing.str += 1
            }
            else if (student.second_tranch < second_tranch ) {
                global.avanced.str += 1
            }
            else {
                global.payed.str += 1
            }

            if (student.third_tranch == 0) {
                global.nothing.ttr += 1
            }
            else if (student.third_tranch < third_tranch ) {
                global.avanced.ttr += 1
            }
            else {
                global.payed.ttr += 1
            }

            if (student.graduation == 0) {
                global.nothing.gra += 1
            }
            else if (student.graduation < graduation ) {
                global.avanced.gra += 1
            }
            else {
                global.payed.gra += 1
            }

            if (student.assurance == 0) {
                global.nothing.ass += 1
            }
            else if (student.assurance < 3000 ) {
                global.avanced.ass += 1
            }
            else {
                global.payed.ass += 1
            }

            if (totalPayed == 0) {
                global.nothing.general += 1
            }
            else if (restToPay > 0) {
                global.avanced.general += 1
            }
            else {
                global.payed.general += 1
            }
            global.total.assurance += student.assurance;
            global.total.inscription += student.inscription;
            global.total.first_tranch += student.first_tranch;
            global.total.second_tranch += student.second_tranch;
            global.total.third_tranch += student.third_tranch;
            global.total.graduation += student.graduation;
        });
        global.over.general = global.over.assurance + global.over.inscription + global.over.first_tranch + global.over.second_tranch + global.over.third_tranch + global.over.graduation
                                
        
        global.total.general = global.total.assurance + global.total.inscription + global.total.first_tranch + global.total.second_tranch + global.total.third_tranch + global.total.graduation;
        req.connection.query(`SELECT p.amount, p.recu_name, p.tag, p.student_id, p.id, c.name as class_name, s.name, p.created_at
                            FROM payments_details p 
                            JOIN students s ON s.id = p.student_id 
                            JOIN class c ON c.id = s.class_id`, 
                            [], (err, payments) => {
            if(err) console.log(err);
            
            const html = downloadFs.readFileSync('src/templates/recettes.html', 'utf-8');
            let name = '';
            let error = 'Aucun payement effectue';
            if (req.params.type === '1') { 
                let date = new Date(req.params.date).getDate() + '/' + ( new Date(req.params.date).getMonth() + 1) + '/' + new Date(req.params.date).getFullYear()
                name = `Recette Journaliere (${date})`.replaceAll('/', '-');
                payments = payments.length > 0 ? payments.filter(p => {
                    let payment_date = new Date(p.created_at).getDate() + '/' + 
                                        ( new Date(p.created_at).getMonth() + 1).toString() +  '/' + 
                                        new Date(p.created_at).getFullYear();
                    
                    if (date === payment_date) {
                        return p;
                    }
                }) : [];
                error += ` le ${date}`;
            }
            else if (req.params.type === '3') {
                let date = new Date(req.params.date).getDate() + '/' + ( new Date(req.params.date).getMonth() + 1) + '/' + new Date(req.params.date).getFullYear()
                let to = new Date(req.params.to).getDate() + '/' + ( new Date(req.params.to).getMonth() + 1) + '/' + new Date(req.params.to).getFullYear()
                name = `Recette periodique du ${date} au ${to}`.replaceAll('/', '-');
                payments = payments.length > 0 ? payments.filter(p => {
                    let d1 = new Date(req.params.date).getTime();
                    let d2 = new Date(req.params.to).getTime();
                    let r = new Date(p.created_at).getFullYear() + '-' +( new Date(p.created_at).getMonth() + 1) + '-' + new Date(p.created_at).getDate();
                    let check = new Date(r).getTime();
                    if((check <= d2 && check >= d1)){
                        return p;
                    }
                }) : [];
                error += ` du ${date} au ${to}`;
            }
            else{
                name = 'Recette Globale'
            }
            let total = 0;
            let student_nber = 0;
            let stuIds = [];
            payments.forEach(p => {
                let payment_date = new Date(p.created_at).getDate() + '/' +( new Date(p.created_at).getMonth() + 1) + '/' + new Date(p.created_at).getFullYear();
                p.payment_date = payment_date
                total += p.amount;
                if (!stuIds.includes(p.student_id)) {
                    stuIds.push(p.student_id)                    
                }
            });
            student_nber = stuIds.length;
            const fileName = `${name}.pdf`;
            const document = {
                html: html,
                data: {
                    payments,
                    name,
                    total,
                    global,
                    error,
                    student_nber,
                    isOverZero: payments.length > 0
                },
                path: `docs/${fileName}`
            };
            pdf.create(document, optionsPdf)
                .then(resp => {
                    console.log(resp);
                    res.download(`docs/${fileName}`)
                })
                .catch(err => {
                    console.log(err);
                    res.status(201).json(err)
                })
        })
    })
}
module.exports.etat = (req, res) => {
    req.connection.query('SELECT * FROM class', [], (e, classes) => {
        req.connection.query('SELECT * FROM students', [], (ee, students) => {

            let global = {
                payed: {
                    ass: 0,
                    ins: 0,
                    ftr: 0,
                    str: 0,
                    ttr: 0,
                    gra: 0,
                    general: 0
                },
                avanced: {
                    ass: 0,
                    ins: 0,
                    ftr: 0,
                    str: 0,
                    ttr: 0,
                    gra: 0,
                    general: 0
                },
                nothing: {
                    ass: 0,
                    ins: 0,
                    ftr: 0,
                    str: 0,
                    ttr: 0,
                    gra: 0,
                    general: 0
                },
                total: {
                    inscription: 0,
                    first_tranch: 0,
                    second_tranch: 0,
                    third_tranch: 0,
                    graduation: 0,
                    assurance: 0,
                    general: 0
                }
            };
            students.forEach(student => {
                const classe = classes.filter(c => c.id === student.class_id);
                
                const inscription = student.status === 'old' ? classe.inscriptions_olds_students : classe.inscriptions_news_students
                const first_tranch = student.status === 'old' ? classe.first_tranch_olds_students : classe.first_tranch_news_students
                const second_tranch = student.status === 'old' ? classe.second_tranch_olds_students : classe.second_tranch_news_students;
                const third_tranch = student.status === 'old' ? classe.third_tranch_olds_students : classe.third_tranch_news_students;
                const graduation = classe.graduation ? classe.graduation : 0;

                const inscription_rest = inscription - student.inscription;
                const first_tranch_rest = first_tranch - student.first_tranch;
                const second_tranch_rest = second_tranch - student.second_tranch;
                const third_tranch_rest = third_tranch - student.third_tranch;
                const graduation_rest = graduation - student.graduation;
                const assurance_rest = 3000 - student.assurance;
        
                const restToPay = (inscription + first_tranch + second_tranch + third_tranch + graduation + 3000) - (student.inscription + student.first_tranch + student.assurance + student.second_tranch + student.third_tranch + student.graduation);
                const totalToPay = inscription + first_tranch + second_tranch + third_tranch + graduation + 3000;
                const totalPayed = (student.inscription + student.first_tranch + student.second_tranch + student.third_tranch + student.graduation + student.assurance);
                // total.inscription += student.inscription;
                // total.first_tranch += student.first_tranch;
                // total.second_tranch += student.second_tranch;
                // total.third_tranch += student.third_tranch;
                // total.graduation += student.graduation;
                // total.assurance += student.assurance;
                // total.general += totalPayed

                student.totalPayed = totalPayed;
                student.inscription_scolarity = inscription;
                student.first_tranch_scolarity = first_tranch;
                student.second_tranch_scolarity = second_tranch;
                student.third_tranch_scolarity = third_tranch;
                student.graduation_scolarity = graduation > 0 ? graduation : null;
                student.totalToPay = totalToPay;

                if (student.inscription == 0) {
                    global.nothing.ins += 1
                }
                else if (student.inscription < inscription ) {
                    global.avanced.ins += 1
                }
                else {
                    global.payed.ins += 1
                }

                if (student.first_tranch == 0) {
                    global.nothing.ftr += 1
                }
                else if (student.first_tranch < first_tranch ) {
                    global.avanced.ftr += 1
                }
                else {
                    global.payed.ftr += 1
                }                    
                
                if (student.second_tranch == 0) {
                    global.nothing.str += 1
                }
                else if (student.second_tranch < second_tranch ) {
                    global.avanced.str += 1
                }
                else {
                    global.payed.str += 1
                }

                if (student.third_tranch == 0) {
                    global.nothing.ttr += 1
                }
                else if (student.third_tranch < third_tranch ) {
                    global.avanced.ttr += 1
                }
                else {
                    global.payed.ttr += 1
                }

                if (student.graduation == 0) {
                    global.nothing.gra += 1
                }
                else if (student.graduation < graduation ) {
                    global.avanced.gra += 1
                }
                else {
                    global.payed.gra += 1
                }

                if (student.assurance == 0) {
                    global.nothing.ass += 1
                }
                else if (student.assurance < 3000 ) {
                    global.avanced.ass += 1
                }
                else {
                    global.payed.ass += 1
                }

                if (totalPayed == 0) {
                    global.nothing.general += 1
                }
                else if (restToPay > 0) {
                    global.avanced.general += 1
                }
                else {
                    global.payed.general += 1
                }
                global.total.assurance += student.assurance;
                global.total.inscription += student.inscription;
                global.total.first_tranch += student.first_tranch;
                global.total.second_tranch += student.second_tranch;
                global.total.third_tranch += student.third_tranch;
                global.total.graduation += student.graduation;


            })
            global.total.general = global.total.assurance + global.total.inscription + global.total.first_tranch + global.total.second_tranch + global.total.third_tranch + global.total.graduation
            classes.forEach(classe => {
                const total = {
                    inscription: 0,
                    first_tranch: 0,
                    second_tranch: 0,
                    third_tranch: 0,
                    graduation: 0,
                    assurance: 0,
                    general: 0
                }
                let global_by_class = {
                    payed: {
                        ass: 0,
                        ins: 0,
                        ftr: 0,
                        str: 0,
                        ttr: 0,
                        gra: 0,
                        general: 0
                    },
                    avanced: {
                        ass: 0,
                        ins: 0,
                        ftr: 0,
                        str: 0,
                        ttr: 0,
                        gra: 0,
                        general: 0
                    },
                    nothing: {
                        ass: 0,
                        ins: 0,
                        ftr: 0,
                        str: 0,
                        ttr: 0,
                        gra: 0,
                        general: 0
                    },
                    total: {
                        inscription: 0,
                        first_tranch: 0,
                        second_tranch: 0,
                        third_tranch: 0,
                        graduation: 0,
                        assurance: 0,
                        general: 0
                    }
                };
                const st = students.filter(s => s.class_id == classe.id).forEach(student => {
                    
                    const inscription = student.status === 'old' ? classe.inscriptions_olds_students : classe.inscriptions_news_students
                    const first_tranch = student.status === 'old' ? classe.first_tranch_olds_students : classe.first_tranch_news_students
                    const second_tranch = student.status === 'old' ? classe.second_tranch_olds_students : classe.second_tranch_news_students;
                    const third_tranch = student.status === 'old' ? classe.third_tranch_olds_students : classe.third_tranch_news_students;
                    const graduation = classe.graduation ? classe.graduation : 0;
    
                    const inscription_rest = inscription - student.inscription;
                    const first_tranch_rest = first_tranch - student.first_tranch;
                    const second_tranch_rest = second_tranch - student.second_tranch;
                    const third_tranch_rest = third_tranch - student.third_tranch;
                    const graduation_rest = graduation - student.graduation;
                    const assurance_rest = 3000 - student.assurance;
    
                    const restToPay = (inscription + first_tranch + second_tranch + third_tranch + graduation + 3000) - (student.inscription + student.first_tranch + student.assurance + student.second_tranch + student.third_tranch + student.graduation);
                    const totalToPay = inscription + first_tranch + second_tranch + third_tranch + graduation + 3000;
                    const totalPayed = (student.inscription + student.first_tranch + student.second_tranch + student.third_tranch + student.graduation + student.assurance);
                    total.inscription += student.inscription;
                    total.first_tranch += student.first_tranch;
                    total.second_tranch += student.second_tranch;
                    total.third_tranch += student.third_tranch;
                    total.graduation += student.graduation;
                    total.assurance += student.assurance;
                    total.general += totalPayed

                    
                    if (student.inscription == 0) {
                        global_by_class.nothing.ins += 1
                    }
                    else if (student.inscription < inscription ) {
                        global_by_class.avanced.ins += 1
                    }
                    else {
                        global_by_class.payed.ins += 1
                    }

                    if (student.first_tranch == 0) {
                        global_by_class.nothing.ftr += 1
                    }
                    else if (student.first_tranch < first_tranch ) {
                        global_by_class.avanced.ftr += 1
                    }
                    else {
                        global_by_class.payed.ftr += 1
                    }                    
                    
                    if (student.second_tranch == 0) {
                        global_by_class.nothing.str += 1
                    }
                    else if (student.second_tranch < second_tranch ) {
                        global_by_class.avanced.str += 1
                    }
                    else {
                        global_by_class.payed.str += 1
                    }

                    if (student.third_tranch == 0) {
                        global_by_class.nothing.ttr += 1
                    }
                    else if (student.third_tranch < third_tranch ) {
                        global_by_class.avanced.ttr += 1
                    }
                    else {
                        global_by_class.payed.ttr += 1
                    }

                    if (student.graduation == 0) {
                        global_by_class.nothing.gra += 1
                    }
                    else if (student.graduation < graduation ) {
                        global_by_class.avanced.gra += 1
                    }
                    else {
                        global_by_class.payed.gra += 1
                    }

                    if (student.assurance == 0) {
                        global_by_class.nothing.ass += 1
                    }
                    else if (student.assurance < 3000 ) {
                        global_by_class.avanced.ass += 1
                    }
                    else {
                        global_by_class.payed.ass += 1
                    }

                    if (totalPayed == 0) {
                        global_by_class.nothing.general += 1
                    }
                    else if (restToPay > 0) {
                        global_by_class.avanced.general += 1
                    }
                    else {
                        global_by_class.payed.general += 1
                    }
                })
                classe.global_by_class = global_by_class;
                classe.total = total
                classe.students = st
                return classe
            })
            const html = downloadFs.readFileSync(`src/templates/rec_glob.html`, 'utf-8');
            const document = {
                html: html,
                data: {
                    classes,
                    global
                },
                path: `docs/recette_globale.pdf`
            };
            pdf.create(document, optionsPdf)
                .then(() => {
                    res.download(`docs/recette_globale.pdf`)
                })
                .catch(err => {
                    console.log(err);
                    
                    res.status(201).json(err)
                })
        })
    })
}

module.exports.getCsvNoteImport = (req, res) => {
    const {section, class_id} = req.params;
    let ar = ['name'];
    req.connection.query(`SELECT name FROM students 
                                WHERE class_id = ? AND is_new = "no"ORDER BY name ASC`, 
                            [class_id], function (err, oldStudents) {
        req.connection.query(`SELECT name FROM students 
                                WHERE class_id = ? AND is_new = "no" ORDER BY name ASC`, 
            [class_id] , (err, newStudents) => {
                let users = [];
                if (newStudents.length > 0) {
                    users = [...oldStudents, ...newStudents]
                }
                else{
                    users = [...oldStudents]
                }
        
                
                req.connection.query(`SELECT sub.id, sub.name FROM subjects  sub
                                        JOIN sections sec
                                        ON sec.id = sub.section 
                                        WHERE sec.type = ?`, [section], (err2, subjects) => {
                    
                    if(err2)console.log(err2);
                    
                    subjects.forEach(sub => {
                        ar.push(sub.name)
                    })
                    
                    
                    const jsonUsers = JSON.parse(JSON.stringify(users));
                    const json2csvParser = new Json2csvParser([...ar]);
                    const csv = json2csvParser.parse(jsonUsers);
                    const csvFile = `docs/import note.csv`;
                    downloadFs.writeFile(csvFile, csv, function (err, csv) {
                        if (err) return console.log(err);
                        else res.download(csvFile);
                    });
                })
        })
    });
}

module.exports.getRecu = (req, res) => {
    const {recu_name} = req.params;
    req.connection.query('SELECT * FROM payments WHERE recu_name = ? ', [recu_name], (err, payments) => {
        req.connection.query('SELECT * FROM students WHERE id = ?', [payments[0].student_id], (err2, students) => {
            const student = students[0];
            const fileName =  `Reçu de ${student.name} ${student.subname} ${new Date(payments[0].created_at).getFullYear()}-${
                new Date(payments[0].created_at).getMonth()}-${new Date(payments[0].created_at).getDate()}.pdf`;
            
            try {
                var data = downloadFs.readFileSync('docs/'+fileName);
                res.contentType("application/pdf");
                res.send(data);
            } catch (error) {
                res.status(401).json({success: false, message: 'Recu introuvable'})
            }
        })
    })
}