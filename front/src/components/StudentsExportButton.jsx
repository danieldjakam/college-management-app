import React, { useState } from 'react';
import { Dropdown } from 'react-bootstrap';
import { Download, FileEarmark } from 'react-bootstrap-icons';
import StudentsExportModal from './StudentsExportModal';

const StudentsExportButton = ({ 
    seriesId = null,
    seriesName = "",
    className = "",
    size = "sm"
}) => {
    const [showModal, setShowModal] = useState(false);

    const openExportModal = () => {
        setShowModal(true);
    };

    return (
        <>
            <Dropdown className={className}>
                <Dropdown.Toggle 
                    variant="outline-secondary" 
                    size={size}
                    className="d-flex align-items-center"
                >
                    <FileEarmark className="me-1" />
                    Export Liste
                </Dropdown.Toggle>

                <Dropdown.Menu>
                    <Dropdown.Item onClick={openExportModal}>
                        <Download className="me-2" />
                        Exporter la liste des élèves
                    </Dropdown.Item>
                </Dropdown.Menu>
            </Dropdown>

            <StudentsExportModal
                show={showModal}
                onHide={() => setShowModal(false)}
                seriesId={seriesId}
                seriesName={seriesName}
            />
        </>
    );
};

export default StudentsExportButton;