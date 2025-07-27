import React, { useState } from 'react'
import { Modal } from 'reactstrap';
import SelectClasse from './Class/SelectClasse';

function Docs() {
    const [isSelectClass, setSelectClass] = useState(false);
    const [type, setType] = useState('pdf');
    const [error, setError] = useState('');
    
    return (
        <div className='container'>
            
            <div style={{margin: '10px 5px', display: 'flex', alignItems: 'center',
                            border: '2px solid #dedede', borderRadius: '20px', 
                            padding: '.15em 1em', width: 'max-content'}}>
                <span style={{fontSize: '42px', fontWeight: '800', marginRight: '20px'}}>Liste des eleves : </span>
                <button 
                    onClick={() => {setSelectClass(v => !v); setType('pdf')}} 
                    className={`btn btn-info`}>
                    en Pdf
                </button> 
                <button style={{marginLeft: '10px'}} 
                    onClick={() => {setSelectClass(v => !v); setType('csv')}} 
                    className={`btn btn-info`}>
                    en Csv
                </button> 
            </div>
            
            <div style={{margin: '10px 5px', display: 'flex', alignItems: 'center',
                            border: '2px solid #dedede', borderRadius: '20px', 
                            padding: '.15em 1em', width: 'max-content'}}>
                <span style={{fontSize: '42px', fontWeight: '800', marginRight: '20px'}}>Tableau des ages : </span>
                <button style={{marginLeft: '10px'}} 
                    onClick={() => {setSelectClass(v => !v); setType('table')}} 
                    className={`btn btn-success`}>
                    Choisir
                </button> 
            </div>
            <Modal isOpen={isSelectClass}>
                <SelectClasse error={error} setError={setError} type={type} setSelectClass={setSelectClass}/>
            </Modal>
        </div>
  )
}

export default Docs