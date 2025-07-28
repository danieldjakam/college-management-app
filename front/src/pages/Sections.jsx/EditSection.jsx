import React from 'react'
import { useEffect } from "react";
import { useState } from "react";
import { sectionTraductions } from '../../local/section';
import { useApi } from '../../hooks/useApi';
import { apiEndpoints } from '../../utils/api';
import { getLang } from '../../utils/lang';

const EditSection = ({error, id, setError, setIsEditSection}) => {
    const { execute, loading } = useApi();
    const [section, setSection] = useState({});
    const types = [1, 2, 3, 4, 5];
    
    useEffect(() => {
        const loadSection = async () => {
            try {
                const data = await execute(() => apiEndpoints.getOneSection(id));
                setSection(data);
            } catch (err) {
                setError(`Erreur lors du chargement de la section: ${err.message}`);
            }
        };
        loadSection();
    }, [id]);
    
    const handleCancel = () => {
      setIsEditSection(false)
      setError('')
    }

    const handleUpdate = async (e) => {
        e.preventDefault();
        try {
            await execute(() => apiEndpoints.updateSection(id, section));
            window.location.reload();
        } catch (err) {
            setError(`Erreur lors de la mise à jour: ${err.message}`);
        }
    }
    return <div className="card login-card">
    <div className="card-head">
      <h1>{sectionTraductions[getLang()].editdSection}</h1>
    </div>
    <form onSubmit={(e) => {handleUpdate(e)}}>
        <div className="card-content">
            <div className="field">
                <div className="label">{sectionTraductions[getLang()].seqName}</div>
                <input type="text" value={section.name} onChange={(e) => {setSection(val => {return {...val, name: e.target.value}})}} placeholder={sectionTraductions[getLang()].seqName} />
            </div>
            <div className="field" style={{display: 'flex'}}>
                    <label className="label">{sectionTraductions[getLang()].type}</label>
                    <select value={section.type} className="form-control" onChange={(e) => {setSection(val => {return{...val, type: e.target.value}})}}>
                        {
                                types.map(type => {
                                    return <option key={type} value={type}>
                                        {type}
                                    </option>
                                })
                        }
                    </select>
            </div> 
            {
            error !== '' ? <div className="error">{error}</div> : ''
            } 
        </div>
        <div className="card-footer">
            <button className="btn btn-blue" type="submit">{loading ? sectionTraductions[getLang()].saving : sectionTraductions[getLang()].save}</button>
            <button onClick={() => {handleCancel()}} type="reset"> {sectionTraductions[getLang()].close}</button>
        </div>
      
    </form>
</div>
}

export default EditSection;