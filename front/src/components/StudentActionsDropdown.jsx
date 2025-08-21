import React from 'react';
import { Dropdown } from 'react-bootstrap';
import { 
    ThreeDotsVertical,
    Printer,
    ArrowRightCircle,
    PencilSquare,
    Trash,
    CashCoin,
    Eye
} from 'react-bootstrap-icons';

const StudentActionsDropdown = ({ 
    student, 
    onPrintCard, 
    onTransfer, 
    onEdit, 
    onDelete, 
    onViewPayments,
    onViewStudent,
    userRole 
}) => {
    return (
        <Dropdown align="end">
            <Dropdown.Toggle 
                variant="outline-secondary" 
                size="sm"
                className="btn-actions-dropdown"
                style={{ 
                    border: 'none',
                    backgroundColor: 'transparent',
                    color: '#6c757d',
                    boxShadow: 'none'
                }}
            >
                <ThreeDotsVertical size={16} />
            </Dropdown.Toggle>

            <Dropdown.Menu className="shadow-sm">
                {/* Voir l'élève */}
                <Dropdown.Item 
                    onClick={() => onViewStudent?.(student)}
                    className="d-flex align-items-center"
                >
                    <Eye size={16} className="me-2 text-info" />
                    Voir l'élève
                </Dropdown.Item>
                {/* Paiements (pour les comptables/admins) */}
                {(userRole === 'secretaire') && (
                    <Dropdown.Item 
                        onClick={() => onViewPayments?.(student)}
                        className="d-flex align-items-center"
                    >
                        <CashCoin size={16} className="me-2 text-success" />
                        Voir paiements
                    </Dropdown.Item>
                )}

                <Dropdown.Divider />

                {/* Imprimer carte */}
                <Dropdown.Item 
                    onClick={() => onPrintCard?.(student)}
                    className="d-flex align-items-center"
                >
                    <Printer size={16} className="me-2 text-success" />
                    Imprimer la carte
                </Dropdown.Item>

                {/* Transférer */}

                {(userRole === 'admin' || userRole === 'secretaire') && (
                    <>
                        <Dropdown.Divider />
                        <Dropdown.Item 
                            onClick={() => onTransfer?.(student)}
                            className="d-flex align-items-center"
                        >
                            <ArrowRightCircle size={16} className="me-2 text-warning" />
                            Transférer
                        </Dropdown.Item>


                        {/* Modifier */}
                        <Dropdown.Item 
                            onClick={() => onEdit?.(student)}
                            className="d-flex align-items-center"
                        >
                            <PencilSquare size={16} className="me-2 text-primary" />
                            Modifier
                        </Dropdown.Item>
                        <Dropdown.Divider />
                        <Dropdown.Item 
                            onClick={() => onDelete?.(student)}
                            className="d-flex align-items-center text-danger"
                        >
                            <Trash size={16} className="me-2" />
                            Supprimer
                        </Dropdown.Item>
                    </>
                )}


                {/* Supprimer */}
            </Dropdown.Menu>
        </Dropdown>
    );
};

export default StudentActionsDropdown;