import React from 'react'
import { useEffect } from "react";
import { useState } from "react";
import ReactLoading from 'react-loading';
import { Link, useParams } from "react-router-dom";
import { Modal } from "reactstrap";
import { handleChangeCsvFile } from '../../utils/functions';
import { studentTraductions } from '../../local/student';
import { getLang } from '../../utils/lang';
import * as Swal from 'sweetalert2';
import { useApi } from '../../hooks/useApi';
import { apiEndpoints } from '../../utils/api';
import AddStudent from './AddStudent';
import EditStudent from './EditStudent';
import { host } from '../../utils/fetch';
  

const Student = () => {
    const params = useParams();
    const {id} = params;
    const { execute, loading } = useApi();
    const [classs, setClass] = useState({});
    const [students, setStudents] = useState({});
    const [error, setError] = useState('');
    const [loadingDel, setLoadingDel] = useState(false);
    const [isAddStudent, setIsAddStudent] = useState(false);
    const [isEditStudent, setIsEditStudent] = useState(false);
    const [studentToEditId, setStudentToEditId] = useState('')
    const val = localStorage.isOrdonned !== null 
    && localStorage.isOrdonned !== undefined ? 
        localStorage.isOrdonned
    : false;
    const [isOrdonned, setIsOrdonned] = useState(val);
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

    useEffect(() => {
        const loadClass = async () => {
            try {
                const data = apiEndpoints.getOneClass();
                setClass(data || {});
            } catch (err) {
                setError('Erreur lors du chargement de la classe');
            }
        };
        loadClass();
    }, [id])

    useEffect(() => {
        const loadStudents = async () => {
            try {
                const data = !isOrdonned 
                    ? await apiEndpoints.getOrderedStudents(id)
                    : await apiEndpoints.getStudentsByClass(id);
                setStudents(data || []);
            } catch (err) {
                setError('Erreur lors du chargement des étudiants');
            }
        };
        loadStudents();
    }, [id, isOrdonned])


    const deleteStudent = async (id) => {
        const result = await Swal.fire({
            title: 'Confirmez la suppression !',
            icon: 'question',
            text: 'Cette action est irreversible !!',
            showCancelButton: true,
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        });
        
        if (result.isConfirmed) {
            try {
                setLoadingDel(true);
                await execute(() => apiEndpoints.deleteStudent(id));
                window.location.reload();
            } catch (err) {
                setError('Erreur lors de la suppression');
            } finally {
                setLoadingDel(false);
            }
        }
    }
    const getOrdonnedStudents = async () => {
        setIsOrdonned(v => !v);
        localStorage.setItem('isOrdonned', !isOrdonned); 
        
        try {
            const data = isOrdonned 
                ? await execute(() => apiEndpoints.getOrderedStudents(id))
                : await execute(() => apiEndpoints.getStudentsByClass(id));
            setStudents(data || []);
        } catch (err) {
            setError('Erreur lors du chargement des étudiants');
        }
    }

    return <div style={{padding: '10px 10px'}} className='container'>
            
        {
            sessionStorage.stat === 'ad' ? <>
                <Link to={`/transfert/${id}`} className="btn btn-primary" style={{ marginBottom: '10px', marginLeft: '10px' }}>
                    <svg width="16" height="16" fill="currentColor" className="bi bi-airplane-engines-fill" viewBox="0 0 16 16">
                        <path d="M8 0c-.787 0-1.292.592-1.572 1.151A4.347 4.347 0 0 0 6 3v3.691l-2 1V7.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.191l-1.17.585A1.5 1.5 0 0 0 0 10.618V12a.5.5 0 0 0 .582.493l1.631-.272.313.937a.5.5 0 0 0 .948 0l.405-1.214 2.21-.369.375 2.253-1.318 1.318A.5.5 0 0 0 5.5 16h5a.5.5 0 0 0 .354-.854l-1.318-1.318.375-2.253 2.21.369.405 1.214a.5.5 0 0 0 .948 0l.313-.937 1.63.272A.5.5 0 0 0 16 12v-1.382a1.5 1.5 0 0 0-.83-1.342L14 8.691V7.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v.191l-2-1V3c0-.568-.14-1.271-.428-1.849C9.292.591 8.787 0 8 0Z"/>
                    </svg>
                    Transfert
                </Link>
            </> : <></>
        }

        <nav className="navbar navbar-expand-lg" style={{padding: '10px 10px', display:"flex", justifyContent:'space-between'}}>
            <h2 style={{marginLeft  : '40px'}}>{studentTraductions[getLang()].studentList} {classs.name}</h2>
            <div style={{marginRight: '10px'}} className='nav item'>
                <ul className="navbar-nav" style={{fontSize: '1.3rem'}}>
                    {
                        sessionStorage.stat === 'ad' ? <>
                            <button onClick={() => {setIsAddStudent(v => !v)}} className="btn btn-blue">{studentTraductions[getLang()].addStudent}</button>
                            <label htmlFor='csvFile' style={{marginLeft: '10px'}} className="btn btn-success">{studentTraductions[getLang()].importStudent}</label>
                            <input type="file" accept='.csv' id='csvFile' style={{display: 'none'}} onChange={(e) => {handleChangeCsvFile(e, '/upload/students/csv/'+id, setError)}} />
                        </> : <></>
                    }
                    {
                        sessionStorage.stat === 'ad' ? 
                                    <button onClick={() => {getOrdonnedStudents()}} 
                                    style={{marginLeft: '10px'}} className="btn btn-blue">
                                        {studentTraductions[getLang()].range}
                                    </button>
                        : <></>
                    }
                    
                </ul>
            </div>

        </nav>
        <nav className=" " style={{padding: '10px 10px'}}>
            <div className="colla" id="navbarNav" style={{padding: '10px 10px', display:"flex", justifyContent:'space-between'}}>
                <a target={'_blank'} rel='noreferrer' href={host+'/download/csv/students/'+id} className="btn btn-primary">{studentTraductions[getLang()].downloadCsv}</a>
                    {
                        sessionStorage.stat === 'ad' ? 
                            <>
                                <label htmlFor='csvFile' style={{marginLeft: '10px'}} className="btn btn-primary">Modifier les eleves</label>
                                <input type="file" accept='.csv' id='csvFile' style={{display: 'none'}} onChange={(e) => {handleChangeCsvFile(e, '/upload/students/csv/modify/'+id, setError)}} />
                            </>
                        : <></>
                    }<a target={'_blank'} rel='noreferrer' style={{marginLeft: '30px'}} href={`${host}/download/table/students/${id}`} className="btn btn-primary">Tableau des ages</a>
                <a target={'_blank'} rel='noreferrer' style={{marginLeft: '30px'}} href={`${host}/download/pdf/students/${id}/${JSON.stringify([])}`} className="btn btn-primary">{studentTraductions[getLang()].downloadPdf}</a>
            </div>
        </nav>
        <table className="table table-dark table-bordered table-striped">
            <thead>
                <tr>
                    <td>{studentTraductions[getLang()].n} </td>
                    <th>{studentTraductions[getLang()].name}</th>
                    <th>{studentTraductions[getLang()].subname}</th>
                    <th>{studentTraductions[getLang()].s}</th>
                    <th>{studentTraductions[getLang()].b}</th>
                    <th>{studentTraductions[getLang()].class}</th>
                    <th>{studentTraductions[getLang()].action}</th>
                </tr>
            </thead>
            <tbody>
                {
                    loading ? <tr>
                                <td colSpan={5} style={{justifyItems: 'center', paddingLeft: '50%'}}>
                                    <ReactLoading color="#fff" type="cylon"/>
                                </td>
                            </tr> : students.length > 0 ? students.map((student, id) => {
                                    let date;
                                    console.log(student);
                                    if (student.birthday) {
                                        date = new Date(student.birthday).getDate() + ' '+ months[new Date(student.birthday).getMonth() + 1] + " " + new Date(student.birthday).getUTCFullYear()
                                    }else{
                                        // date = 'Aucune date de naissance';
                                    }
                                    return <tr key={id}>
                                        <td>{id + 1}</td>
                                        <td>{student.name}</td>
                                        <td>{student.subname}</td>
                                        <td>{student.sex === 'm' ? studentTraductions[getLang()].m : studentTraductions[getLang()].f}</td>
                                        {/* <td>{date}</td> */}
                                        <td>{student.birthday}</td>
                                        <td>{classs.name}</td>
                                        <td style={{display: 'flex', justifyContent: 'space-between'}}>
                                            <button onClick={() => {setStudentToEditId(student.id); setIsEditStudent(v => !v)}} to={`/students/edit/${student.id}`} className="btn btn-warning"> {studentTraductions[getLang()].edit} </button>
                                            {
                                                sessionStorage.stat === 'ad' ? <>
                                                        <button className="btn btn-danger" onClick={() => {deleteStudent(student.id)}}> {loadingDel ? studentTraductions[getLang()].deleting : studentTraductions[getLang()].delete} </button>
                                                </> : <></>
                                            }
                                        </td>
                                    </tr> }) : <tr> 
                                        <td colSpan={7} style={{textAlign: 'center'}}>
                                        {` ${studentTraductions[getLang()].noStudent} ${classs.name} ${studentTraductions[getLang()].now} ${studentTraductions[getLang()].doYou}`} <button onClick={() => {setIsAddStudent(v => !v)}} className="btn btn-blue">{studentTraductions[getLang()].add}</button> ? 
                                        </td>
                                    </tr>
                }
            </tbody>
        </table>

        <Modal isOpen={isAddStudent}>
            <AddStudent error={error} setError={setError} setIsAddStudent={setIsAddStudent}/>
        </Modal>
        <Modal isOpen={isEditStudent}>
            <EditStudent error={error} setError={setError} studentToEditId={studentToEditId} setIsEditStudent={setIsEditStudent}/>
        </Modal>
    </div>
}

export default Student;