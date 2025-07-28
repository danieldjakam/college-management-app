import React from 'react'
import { useEffect } from 'react';
import { useState } from 'react';
import { studentTraductions } from '../../local/student';
import { host } from '../../utils/fetch';
import { getLang } from '../../utils/lang';

function SelectClasse({error, setSelectClass, type, setError}) {
    const [loading, setLoading] = useState(true);
    const [Classes, setClass] = useState([]);
    const [results, setResults] = useState([]);
    const [selected, setSelected] = useState([]);
    const [Champs,] = useState([
        {
            id: 'name',
            name: 'Nom'
        }, 
        {
            id: 'subname',
            name: 'Prenom'
        }, 
        {
            id: 'birthday',
            name: 'Date de naissance'
        }, 
        {
            id: 'birthday_place',
            name: 'Lieu de naissance'
        }, 
        {
            id: 'sex',
            name: 'Sexe'
        }, 
        {
            id: 'fatherName',
            name: 'Nom du parent'
        }, 
        {
            id: 'phone_number',
            name: 'Numero du parent'
        }, 
        {
            id: 'profession',
            name: 'Profession'
        }, 
    ]);
    const handleCancel = () => {
        setError('');
        setSelectClass(false);
    }

    useEffect(() => {
        (
            async () => {
                setLoading(true)
                const resp = await fetch(host+'/class/getAll', {headers: {
                    'Authorization': sessionStorage.user
                  }})
                const data = await resp.json();
                setClass(data);
                setResults(data);
                setLoading(false);
            }
        )()
    }, [])
    const search = ( value ) => {
        setClass(results.filter(clas => clas.name.toLowerCase().includes(value.toLowerCase())));
    }
    const select = (id) => {
        let t = selected;
        if (!t.includes(id)) {
            t = [...t, id];   
        }else{
            t = t.filter(tri => tri !== id);
        }

        setSelected(t);
    }
    return ( <div className="card login-card">
        <div className="card-head">
            <h1>
                Choisissez la classe destinataire
            </h1>
            <span onClick={() => {handleCancel()}} style={{ cursor: 'pointer' }} className="text-danger">
                X
            </span>
        </div>
        <div className="card-content">
            <input type="search" className='form-control' placeholder='search' onChange={(e) => {search(e.target.value)}} style={{ marginBottom: '10px' }}/>
            {
                type === 'pdf' ? 
                    <table className='table table-bordered'>
                        <thead className='table-dark'>
                            <tr>
                                <th>Champ</th>
                                <th>Selectionne ?</th>
                            </tr>
                        </thead>
                        <tbody>
                            {
                                Champs.map(t => {
                                    return <tr>
                                        <td>{t.name}</td>
                                        <td>
                                            <input type="checkbox" checked={selected.includes(t.id)} onChange={() => {select(t.id)}}/>
                                        </td>
                                    </tr>
                                })
                            }
                        </tbody>
                    </table>
                : ''
            }
            <table className='table table-bordered'>
                <thead className='table-dark'>
                    <tr>
                        <td>
                            Nom de la classe
                        </td>
                        <td>
                            Action
                        </td>
                    </tr>
                </thead>
                <tbody className='table-ligth table-striped'>
                    {
                        Classes.length > 0 ? 
                                            Classes.map((classe, i) => {
                                                return <tr key={i}>
                                                            <td>
                                                                {
                                                                    classe.name
                                                                }
                                                            </td>
                                                            <td>
                                                                
                                                                <a target={'_blank'} rel='noreferrer' 
                                                                href={`${host}/download/${type}/students/${classe.id}${type === 'pdf' ? `/${JSON.stringify(selected)}` : ''}`} 
                                                                style={{ marginTop: '10px'}}
                                                                className="btn btn-success">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cloud-download-fill" viewBox="0 0 16 16">
                                                                        <path fill-rule="evenodd" d="M8 0a5.53 5.53 0 0 0-3.594 1.342c-.766.66-1.321 1.52-1.464 2.383C1.266 4.095 0 5.555 0 7.318 0 9.366 1.708 11 3.781 11H7.5V5.5a.5.5 0 0 1 1 0V11h4.188C14.502 11 16 9.57 16 7.773c0-1.636-1.242-2.969-2.834-3.194C12.923 1.999 10.69 0 8 0zm-.354 15.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 14.293V11h-1v3.293l-2.146-2.147a.5.5 0 0 0-.708.708l3 3z"/>
                                                                    </svg>   
                                                                </a>
                                                            </td>
                                                        </tr>
                                            })
                                            : 
                                            <tr>
                                                <td colSpan={2}>
                                                    Aucune classe
                                                </td>
                                            </tr> 
                    }
                </tbody>
            </table>
            {
                error !== '' ? <div className="error">{error}</div> : ''
            } 
        </div>
        <div className="card-footer">
            <p>
                {
                    loading ? 'En cours d\'exportation' : ''
                }
            </p>
            <button onClick={() => {handleCancel()}} type="reset"> {studentTraductions[getLang()].close}</button>
        </div>
    </div>
    )
}

export default SelectClasse