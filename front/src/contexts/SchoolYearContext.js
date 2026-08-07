import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { secureApiEndpoints } from '../utils/apiMigration';
import { useAuth } from '../hooks/useAuth';

const SchoolYearContext = createContext();

export const useSchoolYear = () => {
    const context = useContext(SchoolYearContext);
    if (!context) {
        throw new Error('useSchoolYear must be used within a SchoolYearProvider');
    }
    return context;
};

export const SchoolYearProvider = ({ children }) => {
    const { isAuthenticated } = useAuth();
    const [activeYears, setActiveYears] = useState([]);
    const [workingYear, setWorkingYear] = useState(null);
    const [loading, setLoading] = useState(true);

    const fetchYears = useCallback(async () => {
        if (!isAuthenticated) return;
        try {
            const [yearsRes, workingRes] = await Promise.all([
                secureApiEndpoints.schoolYears.getActiveYears(),
                secureApiEndpoints.schoolYears.getUserWorkingYear()
            ]);

            // secureApi retourne directement le JSON parsé: { success, data }
            const years = yearsRes?.data || [];
            setActiveYears(Array.isArray(years) ? years : []);

            const working = workingRes?.data;
            if (working) {
                setWorkingYear(working);
            } else {
                // Fallback: utiliser l'année courante de la liste
                const current = years.find(y => y.is_current);
                if (current) setWorkingYear(current);
            }
        } catch (error) {
            console.error('Error fetching school years:', error);
        } finally {
            setLoading(false);
        }
    }, [isAuthenticated]);

    useEffect(() => {
        fetchYears();
    }, [fetchYears]);

    const changeWorkingYear = async (yearId) => {
        try {
            const res = await secureApiEndpoints.schoolYears.setUserWorkingYear(yearId);
            if (res?.success) {
                const selected = activeYears.find(y => y.id === parseInt(yearId));
                if (selected) {
                    setWorkingYear(selected);
                }
                // Recharger la page pour que toutes les données se rafraichissent
                window.location.reload();
            } else {
                console.error('Failed to set working year:', res);
            }
        } catch (error) {
            console.error('Error changing working year:', error);
        }
    };

    const value = {
        activeYears,
        workingYear,
        loading,
        changeWorkingYear,
        refreshYears: fetchYears,
        // Helper: l'ID de l'année de travail pour les appels API
        workingYearId: workingYear?.id || null
    };

    return (
        <SchoolYearContext.Provider value={value}>
            {children}
        </SchoolYearContext.Provider>
    );
};
