import React, { useState, useEffect } from 'react';
import { Link, useParams } from "react-router-dom";
import ReactLoading from 'react-loading';
import { Modal } from "reactstrap";
import * as Swal from 'sweetalert2';

// Components
import AddSequence from '../Sequences/AddSequence';
import AddTrimestre from "../Trimestres/AddTrimestre";
import AddStudent from "./AddStudent";
import EditStudent from "./EditStudent";
import AddAnnualExam from '../AnnualExam/AddAnnualExam';
import EditAnnualExam from '../AnnualExam/EditAnnualExam';
import EditSequence from '../Sequences/EditSequence';
import EditTrimestre from '../Trimestres/EditTrimestre';
import ErrorBoundary from '../../components/ErrorBoundary';

// Utils and hooks
import { host } from '../../utils/fetch';
import { handleChangeCsvFile } from '../../utils/functions';
import { studentTraductions } from '../../local/student';
import { getLang } from '../../utils/lang';
import { useStudents, useSequences, useTrimesters, useApi } from '../../hooks/useApi';
import { apiEndpoints } from '../../utils/api';

const Student = () => {
    const params = useParams();
    const { id } = params;

    // États pour les modals
    const [isAll, setIsAll] = useState(false);
    const [isSeq, setIsSeq] = useState(false);
    const [isEditSeq, setIsEditSeq] = useState(false);
    const [seqId, setSeqId] = useState('');
    const [isTrim, setIsTrim] = useState(false);
    const [isEditTrim, setIsEditTrim] = useState(false);
    const [trimId, setTrimId] = useState('');
    const [isAddStudent, setIsAddStudent] = useState(false);
    const [isAddAnnualExam, setIsAnnualExam] = useState(false);
    const [isEditAnnualExam, setIsEditAnnualExam] = useState(false);
    const [annualId, setAnnualId] = useState('');
    const [isEditStudent, setIsEditStudent] = useState(false);
    const [studentToEditId, setStudentToEditId] = useState('');
    const [annuals_exams, setAnnualsExams] = useState([]);

    // Hooks sécurisés pour la gestion des données
    const {
        students,
        loading: studentsLoading,
        isOrdered,
        toggleOrder,
        deleteStudent,
        refetch: refetchStudents
    } = useStudents(id);

    const {
        items: sequences,
        loading: sequencesLoading,
        deleteItem: deleteSequence,
        refetch: refetchSequences
    } = useSequences();

    const {
        items: trimesters,
        loading: trimestersLoading,
        deleteItem: deleteTrimester,
        refetch: refetchTrimesters
    } = useTrimesters();

    // Hook pour récupérer les informations de la classe
    const {
        data: classs,
        loading: classLoading,
        execute: fetchClass
    } = useApi(
        () => apiEndpoints.getClass(id),
        [id],
        { immediate: true }
    );

    // Hook pour les examens annuels
    const {
        data: annualExams,
        loading: annualExamsLoading,
        execute: fetchAnnualExams
    } = useApi(
        () => apiEndpoints.getAllAnnualExams?.() || Promise.resolve([]),
        [],
        { immediate: true, showErrorMessage: false }
    );

    // Fonction sécurisée pour supprimer un examen annuel
    const deleteAnnualExam = async (examId) => {
        if (!window.Swal) {
            if (!window.confirm('Êtes-vous sûr de vouloir supprimer cet examen annuel ?')) {
                return;
            }
        } else {
            const result = await Swal.fire({
                title: 'Confirmation',
                text: 'Êtes-vous sûr de vouloir supprimer cet examen annuel ?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler'
            });

            if (!result.isConfirmed) {
                return;
            }
        }

        try {
            if (apiEndpoints.deleteAnnualExam) {
                await apiEndpoints.deleteAnnualExam(examId);
                setAnnualsExams(prev => prev.filter(exam => exam.id !== examId));
                
                if (window.Swal) {
                    Swal.fire({
                        title: 'Supprimé',
                        text: 'Examen annuel supprimé avec succès',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            }
        } catch (error) {
            console.error('Error deleting annual exam:', error);
        }
    };

    const months = [
        'Incorrect',
        'Janvier', 'Fevrier', 'Mars', 'Avril', 'Mai', 'Juin',
        'Juillet', 'Aout', 'Septembre', 'Octobre', 'Novembre', 'Decembre'
    ];

    const isLoading = studentsLoading || classLoading;

    return (
        <ErrorBoundary>
            <div style={{padding: '10px 10px'}} className='container'>
                
                {/* Section Admin uniquement */}
                {sessionStorage.stat === 'ad' && (
                    <>
                        {/* Bouton pour afficher/masquer les évaluations */}
                        <button 
                            onClick={() => setIsAll(v => !v)} 
                            className="btn btn-primary" 
                            style={{ marginBottom: '10px' }}
                        >
                            <svg width="16" height="16" fill="currentColor" className="bi bi-menu-down" viewBox="0 0 16 16">
                                <path d="M7.646.146a.5.5 0 0 1 .708 0L10.207 2H14a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h3.793L7.646.146z"/>
                            </svg> 
                            Evaluations
                        </button>

                        <Link 
                            to={`/transfert/${id}`} 
                            className="btn btn-primary" 
                            style={{ marginBottom: '10px', marginLeft: '10px' }}
                        >
                            <svg width="16" height="16" fill="currentColor" className="bi bi-airplane-engines-fill" viewBox="0 0 16 16">
                                <path d="M8 0c-.787 0-1.292.592-1.572 1.151A4.347 4.347 0 0 0 6 3v3.691l-2 1V7.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.191l-1.17.585A1.5 1.5 0 0 0 0 10.618V12a.5.5 0 0 0 .582.493l1.631-.272.313.937a.5.5 0 0 0 .948 0l.405-1.214 2.21-.369.375 2.253-1.318 1.318A.5.5 0 0 0 5.5 16h5a.5.5 0 0 0 .354-.854l-1.318-1.318.375-2.253 2.21.369.405 1.214a.5.5 0 0 0 .948 0l.313-.937 1.63.272A.5.5 0 0 0 16 12v-1.382a1.5 1.5 0 0 0-.83-1.342L14 8.691V7.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v.191l-2-1V3c0-.568-.14-1.271-.428-1.849C9.292.591 8.787 0 8 0Z"/>
                            </svg>
                            Transfert
                        </Link>

                        {/* Section Évaluations */}
                        {isAll && (
                            <>
                                {/* Séquences */}
                                {classs?.type !== 2 && classs?.type !== 1 && (
                                    <>
                                        <div style={{marginBottom: '10px'}}>
                                            <button 
                                                onClick={() => setIsSeq(v => !v)} 
                                                className="btn btn-blue"
                                            >
                                                {studentTraductions[getLang()].addSeq}
                                            </button>
                                        </div>
                                        
                                        <table className="table table-dark table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>{studentTraductions[getLang()].name}</th>
                                                    <th>{studentTraductions[getLang()].specialSeq}</th>
                                                    <th>{studentTraductions[getLang()].action}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {sequencesLoading ? (
                                                    <tr>
                                                        <td colSpan={3} style={{textAlign: 'center'}}>
                                                            <ReactLoading color="#fff" type="cylon"/>
                                                        </td>
                                                    </tr>
                                                ) : sequences?.length > 0 ? (
                                                    sequences.map((sequence, index) => (
                                                        <tr key={sequence.id || index}>
                                                            <td>{sequence.name}</td>
                                                            <td>
                                                                <a 
                                                                    style={{textDecoration: 'none', color: '#fff'}} 
                                                                    href={`/exams${classs?.type}/${sequence.id}/${id}`}
                                                                >
                                                                    {studentTraductions[getLang()].enterData}
                                                                </a>
                                                            </td>
                                                            <td style={{display: 'flex', justifyContent: 'space-between'}}>
                                                                <button 
                                                                    className="btn btn-warning" 
                                                                    onClick={() => {setSeqId(sequence.id); setIsEditSeq(v => !v)}}
                                                                >
                                                                    Editer
                                                                </button>
                                                                <button 
                                                                    className="btn btn-danger" 
                                                                    onClick={() => deleteSequence(sequence.id)}
                                                                > 
                                                                    {studentTraductions[getLang()].delete}
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    ))
                                                ) : (
                                                    <tr>
                                                        <td colSpan={3} style={{textAlign: 'center'}}>
                                                            {studentTraductions[getLang()].noSeq} {studentTraductions[getLang()].doYou} 
                                                            <button onClick={() => setIsSeq(v => !v)} className="btn btn-blue">
                                                                {studentTraductions[getLang()].add}
                                                            </button> ?
                                                        </td>
                                                    </tr>
                                                )}
                                            </tbody>
                                        </table>
                                        <hr />
                                    </>
                                )}

                                {/* Trimestres */}
                                <div style={{marginBottom: '10px'}}>
                                    <button 
                                        onClick={() => setIsTrim(v => !v)} 
                                        className="btn btn-blue"
                                    >
                                        {studentTraductions[getLang()].addTrim}
                                    </button>
                                </div>
                                
                                <table className="table table-dark table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>{studentTraductions[getLang()].name}</th>
                                            <th>{studentTraductions[getLang()].specialTrim}</th>
                                            <th>{studentTraductions[getLang()].action}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {trimestersLoading ? (
                                            <tr>
                                                <td colSpan={3} style={{textAlign: 'center'}}>
                                                    <ReactLoading color="#fff" type="cylon"/>
                                                </td>
                                            </tr>
                                        ) : trimesters?.length > 0 ? (
                                            trimesters.map((trimester, index) => (
                                                <tr key={trimester.id || index}>
                                                    <td>{trimester.name}</td>
                                                    <td>
                                                        <a 
                                                            style={{textDecoration: 'none', color: '#fff'}} 
                                                            href={`/trims${classs?.type}/${trimester.id}/${id}`}
                                                        >
                                                            {studentTraductions[getLang()].seeData}
                                                        </a>
                                                    </td>
                                                    <td style={{display: 'flex', justifyContent: 'space-between'}}>
                                                        <button 
                                                            className="btn btn-warning" 
                                                            onClick={() => {setTrimId(trimester.id); setIsEditTrim(v => !v)}}
                                                        >
                                                            Editer
                                                        </button>
                                                        <button 
                                                            className="btn btn-danger" 
                                                            onClick={() => deleteTrimester(trimester.id)}
                                                        > 
                                                            {studentTraductions[getLang()].delete}
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))
                                        ) : (
                                            <tr>
                                                <td colSpan={3} style={{textAlign: 'center'}}>
                                                    {studentTraductions[getLang()].noTrim} {studentTraductions[getLang()].doYou} 
                                                    <button onClick={() => setIsTrim(v => !v)} className="btn btn-blue">
                                                        {studentTraductions[getLang()].add}
                                                    </button> ?
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                                <hr />
                            </>
                        )}
                    </>
                )}

                {/* En-tête de la liste des étudiants */}
                <nav className="navbar navbar-expand-lg" style={{padding: '10px 10px', display:"flex", justifyContent:'space-between'}}>
                    <h2 style={{marginLeft: '40px'}}>
                        {studentTraductions[getLang()].studentList} {classs?.name || ''}
                    </h2>
                    <div style={{marginRight: '10px'}} className='nav item'>
                        <ul className="navbar-nav" style={{fontSize: '1.3rem'}}>
                            {sessionStorage.stat === 'ad' && (
                                <>
                                    <button 
                                        onClick={() => setIsAddStudent(v => !v)} 
                                        className="btn btn-blue"
                                    >
                                        {studentTraductions[getLang()].addStudent}
                                    </button>
                                    <label htmlFor='csvFile' style={{marginLeft: '10px'}} className="btn btn-success">
                                        {studentTraductions[getLang()].importStudent}
                                    </label>
                                    <input 
                                        type="file" 
                                        accept='.csv' 
                                        id='csvFile' 
                                        style={{display: 'none'}} 
                                        onChange={(e) => {handleChangeCsvFile(e, '/upload/students/csv/'+id, () => {})}} 
                                    />
                                    <button 
                                        onClick={toggleOrder} 
                                        style={{marginLeft: '10px'}} 
                                        className="btn btn-blue"
                                    >
                                        {studentTraductions[getLang()].range}
                                    </button>
                                </>
                            )}
                        </ul>
                    </div>
                </nav>

                {/* Actions de téléchargement */}
                <nav className=" " style={{padding: '10px 10px'}}>
                    <div className="colla" style={{padding: '10px 10px', display:"flex", justifyContent:'space-between'}}>
                        <a 
                            target={'_blank'} 
                            rel='noreferrer' 
                            href={host+'/api/download/csv/students/'+id} 
                            className="btn btn-primary"
                        >
                            {studentTraductions[getLang()].downloadCsv}
                        </a>
                        <a 
                            target={'_blank'} 
                            rel='noreferrer' 
                            style={{marginLeft: '30px'}} 
                            href={`${host}/api/download/table/students/${id}`} 
                            className="btn btn-primary"
                        >
                            Tableau des ages
                        </a>
                        <a 
                            target={'_blank'} 
                            rel='noreferrer' 
                            style={{marginLeft: '30px'}} 
                            href={`${host}/api/download/pdf/students/${id}/${JSON.stringify([])}`} 
                            className="btn btn-primary"
                        >
                            {studentTraductions[getLang()].downloadPdf}
                        </a>
                    </div>
                </nav>

                {/* Tableau des étudiants */}
                <table className="table table-dark table-bordered table-striped">
                    <thead>
                        <tr>
                            <td>{studentTraductions[getLang()].n}</td>
                            <th>{studentTraductions[getLang()].name}</th>
                            <th>{studentTraductions[getLang()].subname}</th>
                            <th>{studentTraductions[getLang()].s}</th>
                            <th>{studentTraductions[getLang()].b}</th>
                            <th>{studentTraductions[getLang()].class}</th>
                            <th>{studentTraductions[getLang()].action}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {isLoading ? (
                            <tr>
                                <td colSpan={7} style={{textAlign: 'center'}}>
                                    <ReactLoading color="#fff" type="cylon"/>
                                </td>
                            </tr>
                        ) : students?.length > 0 ? (
                            students.map((student, index) => {
                                const date = student.birthday 
                                    ? new Date(student.birthday).getDate() + ' ' + 
                                      months[new Date(student.birthday).getMonth() + 1] + " " + 
                                      new Date(student.birthday).getUTCFullYear()
                                    : 'Aucune date de naissance';

                                return (
                                    <tr key={student.id || index}>
                                        <td>{index + 1}</td>
                                        <td>{student.name}</td>
                                        <td>{student.subname}</td>
                                        <td>
                                            {student.sex === 'm' 
                                                ? studentTraductions[getLang()].m 
                                                : studentTraductions[getLang()].f
                                            }
                                        </td>
                                        <td>{student.birthday || 'Non spécifié'}</td>
                                        <td>{classs?.name || ''}</td>
                                        <td style={{display: 'flex', justifyContent: 'space-between'}}>
                                            <button 
                                                onClick={() => {
                                                    setStudentToEditId(student.id); 
                                                    setIsEditStudent(v => !v)
                                                }} 
                                                className="btn btn-warning"
                                            > 
                                                {studentTraductions[getLang()].edit} 
                                            </button>
                                            {sessionStorage.stat === 'ad' && (
                                                <button 
                                                    className="btn btn-danger" 
                                                    onClick={() => deleteStudent(student.id)}
                                                > 
                                                    {studentTraductions[getLang()].delete}
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                );
                            })
                        ) : (
                            <tr> 
                                <td colSpan={7} style={{textAlign: 'center'}}>
                                    {`${studentTraductions[getLang()].noStudent} ${classs?.name || ''} ${studentTraductions[getLang()].now} ${studentTraductions[getLang()].doYou}`} 
                                    {sessionStorage.stat === 'ad' && (
                                        <button onClick={() => setIsAddStudent(v => !v)} className="btn btn-blue">
                                            {studentTraductions[getLang()].add}
                                        </button>
                                    )} ?
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>

                {/* Modales */}
                <Modal isOpen={isSeq}>
                    <AddSequence setError={() => {}} setIsSeq={setIsSeq}/>
                </Modal>
                <Modal isOpen={isEditSeq}>
                    <EditSequence setError={() => {}} setIsEditSeq={setIsEditSeq} id={seqId}/>
                </Modal>
                <Modal isOpen={isTrim}>
                    <AddTrimestre setError={() => {}} setIsTrim={setIsTrim}/>
                </Modal>
                <Modal isOpen={isEditTrim}>
                    <EditTrimestre setError={() => {}} setIsEditTrim={setIsEditTrim} id={trimId}/>
                </Modal>
                <Modal isOpen={isAddStudent}>
                    <AddStudent setError={() => {}} setIsAddStudent={setIsAddStudent}/>
                </Modal>
                <Modal isOpen={isEditStudent}>
                    <EditStudent setError={() => {}} studentToEditId={studentToEditId} setIsEditStudent={setIsEditStudent}/>
                </Modal>
                <Modal isOpen={isAddAnnualExam}>
                    <AddAnnualExam setError={() => {}} setIsAnnualExam={setIsAnnualExam}/>
                </Modal>
                <Modal isOpen={isEditAnnualExam}>
                    <EditAnnualExam setError={() => {}} setIsEditAnnualExam={setIsEditAnnualExam} id={annualId}/>
                </Modal>
            </div>
        </ErrorBoundary>
    );
};

export default Student;