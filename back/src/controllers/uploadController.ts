const csvtojson = require('csvtojson')

module.exports.uploadStudentCsv = (req: any, res:any ) => {
    const {csvText} = req.body;
    
    csvtojson()
        .fromString(csvText)
        .then((students)=>{ 
            students.forEach(student => {
                const {name, subname, birthday, fatherName, phone_number, profession, email, sex, status} = student
                const sta = status ? status : 'old';
                const b = birthday ? birthday : null; 
                req.connection.query(`INSERT INTO students(name, subname, class_id, 
                                            sex, fatherName, profession, birthday, 
                                            email, phone_number, school_year, status, 
                                            school_id) 
                                        VALUES(?, ?, ?, ?,
                                             ?, ?, ?, ?, ?,
                                        ?, ?, ?)`, 
                                        [name, subname, req.params.id, sex, 
                                            fatherName, profession, b, email, 
                                            phone_number.toString(), req.school_year, 
                                            sta, req.payload.school_id], (err, respp) => {

                                            if(err) console.log(err);
                                        });
            })
            res.status(201).json({success: true})
        })
        .catch(err => {
            console.error(err);
        })
}


module.exports.uploadTeacherCsv = (req: any, res:any ) => {
    const {csvText} = req.body;
    
    csvtojson()
        .fromString(csvText)
        .then((teachers)=>{ 
            teachers.forEach(teacher => {
                const t = [];
                for (let i = 0; i < 4; i++) {
                const i = Math.round(Math.random() * 10);
                t.push(i);
                }
                const password = t.join('')


                const {name, subname, birthday, phone_number, sex, class_name} = teacher
                let matricule = class_name.replace(' ', '')
                matricule = 'SEM-'+matricule.toUpperCase()
                req.connection.query('SELECT id FROM class WHERE name = ?', [class_name], (errr, resp) => {
                    req.connection.query(`INSERT INTO teachers(id, name, subname, 
                                            class_id, sex, birthday, 
                                            phone_number, school_id, 
                                            matricule, password) 
                                            VALUES(?, ?, ?, 
                                                ?, ?, ?, 
                                                ?, ?, 
                                                ?, ?)`, 
                                            [req.jwt.sign(name+req.school_year, req.env.SECRET), name, subname, resp[0].id, sex,
                                            birthday, phone_number.toString(), req.payload.school_id, matricule, password], (err, respp) => {
                        if(err) console.log(err);
                    })
                })
            })
            res.status(201).json({success: true})
        })
        .catch(err => {
            console.error(err);
        })
}

module.exports.uploadClassCsv = (req: any, res:any ) => {
    const {csvText} = req.body;
    
    csvtojson()
        .fromString(csvText)
        .then((classes)=>{ 
            classes.forEach(classe => {
                let {
                    name, section_type, level, 
                    inscriptions_olds_students, inscriptions_news_students, 
                    first_tranch_news_students, first_tranch_olds_students, 
                    second_tranch_news_students, second_tranch_olds_students,
                    third_tranch_news_students, third_tranch_olds_students,
                    graduation } = classe;
                req.connection.query('SELECT id FROM sections WHERE type = ? ', [section_type], (err, sections) => {
                    const {id} = sections[0];
                    req.connection.query(`INSERT INTO class(id, name, level, section, 
                        inscriptions_olds_students, inscriptions_news_students, 
                        first_tranch_news_students, first_tranch_olds_students, 
                        second_tranch_news_students, second_tranch_olds_students, 
                        third_tranch_news_students, third_tranch_olds_students,
                        graduation, school_id, school_year) 
                        VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`, 
                        [req.jwt.sign(name, req.env.SECRET), 
                            name, level, id,
                            inscriptions_olds_students, inscriptions_news_students, 
                            first_tranch_news_students, first_tranch_olds_students, 
                            second_tranch_news_students, second_tranch_olds_students, 
                            third_tranch_news_students, third_tranch_olds_students, graduation,
                            req.payload.school_id, req.school_year], (err2: any, resp : any) => {
                                if(err2) console.log(err2);
                    })
                })
            })
            res.status(201).json({success: true})
        })
        .catch(err => {
            console.error(err);
        })
}

module.exports.uploadNoteCsv = (req: any, res:any ) => {
    const {csvText} = req.body;
    
    csvtojson()
        .fromString(csvText)
        .then((notes)=>{ 
            
            // notes.forEach(note => {
            //     const {value, exam_id, student_id, class_id, matiere_id, tag_name} = note;
            //     let val: number = 0;
            //     val = parseFloat(value) > 0 ? value : 0;  
            //     val = parseFloat(value) > 20 ? 20 : value;
            //     req.connection.query('INSERT INTO notes(student_id, exam_id, class_id, matiere_id, tag_name, value) VALUES(?, ?, ?, ?, ?, ?)', [student_id, exam_id, class_id, matiere_id, tag_name, val], (err, respp) => {
            //         if(err) console.log(err);
            //         else res.status(201).json({success: true})
            //     })
            // })
        })
        .catch(err => {
            console.error(err);
        })
}

module.exports.uploadStudentModifyCsv = (req, res) => {
    const {csvText} = req.body;
    
    csvtojson()
        .fromString(csvText)
        .then((students)=>{ 
            students.forEach(student => {
                const {name, birthday_place, birthday, fatherName, phone_number, profession, email, sex, status} = student
                const sta = status ? status : 'old';
                let b = birthday ? birthday : null; 
                b = b.includes('T') ? b : b+'T00:00.00.000Z' ;

                req.connection.query(`UPDATE students SET sex = ?, fatherName = ?, profession = ?, birthday = ?, 
                        email = ?, phone_number = ?, birthday_place = ? WHERE name = ?`, 
                    [sex, 
                        fatherName, profession, b, email, 
                        phone_number.toString(), birthday_place, name], (err, respp) => {

                        if(err) console.log(err);
                });
            })
            res.status(201).json({success: true})
        })
        .catch(err => {
            console.error(err);
        })
}

module.exports.uploadNote = (req, res) => {
    const {exam_id, section, class_id} = req.params;
    const {csvText} = req.body;
    
    
    csvtojson()
        .fromString(csvText)
        .then((students)=>{ 
            console.log(students);
            students.forEach(student => {
                const {name} = student;
                req.connection.query('SELECT id FROM students WHERE name = ?', [name], (err, stu) => {
                    const student_id = stu[0].id;
                    if(err)console.log(err);
                    
                    req.connection.query(`SELECT sub.id, sub.name FROM subjects  sub
                                            JOIN sections sec
                                            ON sec.id = sub.section 
                                            WHERE sec.type = ?`, [section], (err2, subjects) => {
                        
                        if(err2)console.log(err2);
                        
                        subjects.forEach(sub => {
                            const value = student[sub.name] !== undefined && student[sub.name] !== null ? student[sub.name] : 0;
                            console.log(value);
                            const subject_id = sub.id;
                            req.connection.query('INSERT INTO notesBySubject(student_id, exam_id, class_id, subject_id, school_year, value) VALUES(?, ?, ?, ?, ?, ?)', [student_id, exam_id, class_id, subject_id, req.school_year, value], (err2, resp2) => {
                                if(!err2){

                                    req.connection.query('SELECT * FROM notesBySubject WHERE exam_id = ? AND student_id = ? AND class_id = ?', [exam_id, student_id, class_id], (err3, notes) => {
                                        let totalPoints = 0;
                                        notes.forEach(note => {
                                            totalPoints += parseFloat(note.value)
                                        })
                                        
                                        
                                        req.connection.query('SELECT * FROM stats WHERE student_id = ? AND exam_id = ? AND class_id = ?', [student_id, exam_id, class_id], (err, stats) => {
                                            if(stats.length < 1) {
                                                req.connection.query('INSERT INTO stats(student_id, class_id, exam_id, totalPoints) VALUES(?, ?, ?, ?)', [student_id, class_id, exam_id, totalPoints], (err5, resp5) => {
                                                    if(err5) console.log(err5);
                                                    // else res.status(201).json({success: true})
                                                })
                                            }else{
                                                req.connection.query('UPDATE stats SET totalPoints = ? WHERE student_id = ? AND class_id = ? AND exam_id = ? AND school_year = ?', [totalPoints, student_id, class_id, exam_id, req.school_year], (err5, resp5) => {
                                                    if(err5) console.log(err5);
                                                    // else res.status(201).json({success: true})
                                                })
                                            }
                                        })
                                    })
                                }
                            })
                        })
                    })
                    
                })
            // res.status(201).json({success: true})
            })
        .catch(err => {
            console.error(err);
        })
    })
}