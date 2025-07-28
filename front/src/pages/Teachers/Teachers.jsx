import { useState } from "react";
import ReactLoading from 'react-loading';
import { useNavigate } from "react-router-dom";

import * as Swal from 'sweetalert2';
import {
    Modal,
} from "reactstrap"
import AddTeacher from "./AddTeachers";
import EditTeacher from "./EditTeacher";
import ShowMdp from './ShowMdp';
import { host } from '../../utils/fetch';
import { handleChangeCsvFile } from '../../utils/functions';
import { teacherTraductions } from '../../local/teacher';
import { getLang } from '../../utils/lang';
import { useTeachers } from '../../hooks/useApi';
import { apiEndpoints } from '../../utils/api';
import ErrorBoundary from '../../components/ErrorBoundary';


const Teachers = () => {
    const navigate = useNavigate()
    
    if (sessionStorage.stat !== 'ad') {
        navigate('/students/'+sessionStorage.classId)
    }

    // Utilisation du hook useTeachers pour la gestion automatique des erreurs
    const { 
        items: teachers, 
        loading, 
        error, 
        operationLoading, 
        deleteItem: deleteTeacher,
        refetch: reloadTeachers 
    } = useTeachers();

    const [generating, setGenerating] = useState(false);
    const [teacherToEditId, setTeacherToEditId] = useState('')
    const [isAddteacher, setIsAddTeacher] = useState(false);
    const [isEditteacher, setIsEditTeacher] = useState(false);
    const [isSeeMdp, setIsMdp] = useState(false);
    const [mdp, setMdp] = useState('')

    const regeneratePassword = async () => {
        try {
            setGenerating(true);
            await apiEndpoints.regenerateTeacherPasswords();
            
            if (window.Swal) {
                await Swal.fire({
                    title: 'Succès',
                    text: 'Mots de passe régénérés avec succès',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
            
            reloadTeachers();
        } catch (err) {
            // L'erreur est déjà gérée par le système centralisé
            console.error('Error regenerating passwords:', err);
        } finally {
            setGenerating(false);
        }
    }
    return (
        <ErrorBoundary>
            <div style={{padding: '10px 10px'}} className='container'>
                
                <div style={{marginBottom: '10px'}}>
                    <button onClick={() => {setIsAddTeacher(v => !v)}} className="btn btn-blue">
                        {teacherTraductions[getLang()].addTeacher}
                    </button>
                    <label htmlFor='csvFile' style={{marginLeft: '10px'}} className="btn btn-success">
                        {teacherTraductions[getLang()].importTeacher}
                    </label>
                    <input 
                        type="file" 
                        accept='.csv' 
                        id='csvFile' 
                        style={{display: 'none'}} 
                        onChange={(e) => {handleChangeCsvFile(e, '/upload/teachers/csv', () => {})}} 
                    />
                    <button 
                        onClick={regeneratePassword} 
                        style={{marginLeft: '10px'}} 
                        className="btn btn-blue"
                        disabled={generating}
                    >
                        {generating ? teacherTraductions[getLang()].loading : teacherTraductions[getLang()].generateNewMdp}
                    </button>
                    <a 
                        href={host+"/api/teachers/downloadTeachersPassword/"+sessionStorage.user} 
                        target="_blank" 
                        rel="noopener noreferrer" 
                        style={{marginLeft: '10px'}} 
                        className="btn btn-blue"
                    >
                        {teacherTraductions[getLang()].downloadTeacherMdp}
                    </a>
                </div>

                <div className="allClas col-md-12">
                    {loading ? (
                        <div className="error" style={{ position: 'absolute', top: '39%', left: '53%' }}>
                            <ReactLoading color="#fff" type="spin"/>
                        </div>
                    ) : teachers && teachers.length > 0 ? (
                        teachers.map((teacher, id) => (
                            <div className="clas" key={teacher.id || id}>
                                <div className="top">
                                    <div className="classAbs">
                                        {teacher.name}
                                    </div>
                                    <div className="qq">
                                        <span className="q">
                                            {teacherTraductions['fr'].name}: 
                                        </span>
                                        <span className="r">
                                            {teacher.name}
                                        </span>
                                    </div>
                                    <div className="qq">
                                        <span className="q">
                                        {teacherTraductions['fr'].subname}: 
                                        </span>
                                        <span className="r">
                                            {teacher.subname}
                                        </span>
                                    </div>  
                                    <div className="qq">
                                        <span className="q">
                                            {teacherTraductions['fr'].class}: 
                                        </span>
                                        <span className="r">
                                            {teacher.school_class?.name || teacher.className || 'Non assigné'}
                                        </span>
                                    </div>
                                    <div className="qq">
                                        <span className="q">
                                            {teacherTraductions['fr'].section}:
                                        </span>
                                        <span className="r">
                                            {teacher.school_class?.section?.name || teacher.section_name || 'Non assigné'}   
                                        </span>
                                    </div>
                                    <div className="qq">
                                        <span className="q">
                                        {teacherTraductions['fr'].mr}: 
                                        </span>
                                        <span className="r">
                                            {teacher.matricule}
                                        </span>
                                    </div>  
                                </div>
                                <div className="bottom">
                                    <button 
                                        onClick={() => {setMdp(teacher.password); setIsMdp(v => !v);}} 
                                        className="btn btn-warning"
                                    > 
                                        {teacherTraductions[getLang()].seeMdp} 
                                    </button>
                                    <button 
                                        onClick={() => {setIsEditTeacher(v => !v); setTeacherToEditId(teacher.id)}} 
                                        className="btn btn-success"
                                    > 
                                        {teacherTraductions[getLang()].edit}
                                    </button>
                                    <button 
                                        className="btn btn-danger" 
                                        onClick={() => deleteTeacher(teacher.id)}
                                        disabled={operationLoading[`delete_${teacher.id}`]}
                                    > 
                                        {operationLoading[`delete_${teacher.id}`] 
                                            ? teacherTraductions[getLang()].deleting 
                                            : teacherTraductions[getLang()].delete
                                        } 
                                    </button>
                                </div>  
                            </div> 
                        ))
                    ) : (
                        <div className="i">
                            <div className="empty monINfos">
                                {teacherTraductions[getLang()].noTeacher} <br />
                                {teacherTraductions[getLang()].doYou} 
                                <button onClick={() => {setIsAddTeacher(v => !v)}} className="btn btn-blue"> 
                                    {teacherTraductions[getLang()].add} 
                                </button>
                            </div>
                        </div>
                    )}
                </div>

                
                <Modal isOpen={isAddteacher}>
                    <AddTeacher error={error} setError={() => {}} setIsAddTeacher={setIsAddTeacher}/>
                </Modal>

                <Modal isOpen={isSeeMdp}>
                    <ShowMdp setIsMdp={setIsMdp} mdp={mdp}/>
                </Modal>

                <Modal isOpen={isEditteacher}>
                    <EditTeacher error={error} setError={() => {}} setIsEditClass={setIsEditTeacher} teacherToEditId={teacherToEditId}/>
                </Modal>
            </div>
        </ErrorBoundary>
    )
}
export default Teachers;