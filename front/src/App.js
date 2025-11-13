import "bootstrap/dist/css/bootstrap.min.css";
import { useEffect, useState } from "react";
import { Route, BrowserRouter as Router, Routes } from "react-router-dom";
import "./App.css";
import "./styles/theme.css";

// Auth Components
import AppAuthProvider from "./components/AuthProvider";
import ProtectedRoute, {
  AccountantRoute,
  AdminRoute,
  NeedsManagementRoute,
  PublicRoute,
  RoleBasedRedirect,
  TeacherRoute,
} from "./components/ProtectedRoute";
import { useAuth } from "./hooks/useAuth";
import { SchoolProvider } from "./contexts/SchoolContext";
import { ThemeProvider } from "./contexts/ThemeContext";

// Pages
import Error404 from "./pages/Error404";
import Levels from "./pages/Levels/Levels";
import Login from "./pages/Login";
import PaymentTranches from "./pages/PaymentTranches";
import SchoolClasses from "./pages/SchoolClasses/SchoolClasses";
import SchoolYears from "./pages/SchoolYears";
import Sections from "./pages/Sections/Sections";
import AdminDashboard from "./pages/Admin/AdminDashboard";
import PrincipalDashboard from "./pages/Principal/PrincipalDashboard";
import Settings from "./pages/Settings";
import GeolocationZoneSettingsV2 from "./pages/Settings/GeolocationZoneSettingsV2";
import UserProfile from "./pages/Profile/UserProfile";
import SeriesStudents from "./pages/Students/SeriesStudents";
import StudentTransfers from "./pages/Students/StudentTransfers";
import StudentsOverview from "./pages/Students/StudentsOverview";

// Comptable Pages
import ClassCompt from "./pages/comptables/Class";
import ParamsCompt from "./pages/comptables/Params";
import StudentsComp from "./pages/comptables/Students";
import StudentsByClass from "./pages/comptables/StudentsByClass";
import StudentAttendanceTracking from "./pages/comptables/StudentAttendanceTracking";
import StaffAttendanceReportCompt from "./pages/comptables/StaffAttendanceReport";

// Payment Pages
import StudentPayment from "./pages/Payments/StudentPayment";
import PaymentReports from "./pages/Payments/PaymentReports";
import DocumentaryFees from "./pages/Payments/DocumentaryFees";
import CreateDocumentaryFee from "./pages/Payments/CreateDocumentaryFee";
import DocumentaryFeeDetails from "./pages/Payments/DocumentaryFeeDetails";
import PaymentManagement from "./pages/Payments/PaymentManagement";

// Reports
import Reports from "./pages/Reports";
import SchoolFeePaymentDetails from "./pages/Reports/SchoolFeePaymentDetails";
import DetailedCollectionReport from "./pages/Reports/DetailedCollectionReport";
import ClassSchoolFeesReport from "./pages/Reports/ClassSchoolFeesReport";
import RecoveryStatus from "./pages/Reports/RecoveryStatus";
import SchoolCertificates from "./pages/Reports/SchoolCertificates";
import StaffAttendanceReport from "./pages/Reports/StaffAttendanceReport";
import ClassFeesSheet from "./pages/Reports/ClassFeesSheet";


// User Management
import UserManagement from "./pages/UserManagement";

// Subjects & Teachers
import Subjects from "./pages/Subjects/Subjects";
import SeriesSubjectConfiguration from "./pages/Subjects/SeriesSubjectConfiguration";
import Teachers from "./pages/Teachers/Teachers";
import TeacherAssignments from "./pages/Teachers/TeacherAssignments";
import TeacherAssignmentManagement from "./pages/Teachers/TeacherAssignmentManagement";

// Teacher Dashboard
import TeacherDashboard from "./pages/Teacher/TeacherDashboard";
import TeacherClassStudents from "./pages/Teacher/TeacherClassStudents";
import Sequences from "./pages/Teacher/Sequences";
import Trimesters from "./pages/Teacher/Trimesters";
import DSDetails from "./pages/Teacher/DSDetails";
import Evaluations from "./pages/Teacher/Evaluations";
import EvaluationCreate from "./pages/Teacher/EvaluationCreate";
import GradeEntry from "./pages/Teacher/GradeEntry";
import SequenceSubjects from "./pages/Teacher/SequenceSubjects";
import SubjectStudents from "./pages/Teacher/SubjectStudents";
import Competences from "./pages/Teacher/Competences";

// Departments
import DepartmentManagement from "./pages/Departments/DepartmentManagement";

// Needs
import MyNeeds from "./pages/Needs/MyNeeds";
import NeedsManagement from "./pages/Needs/NeedsManagement";

// Attendance
import AttendanceScanner from "./pages/Attendance/AttendanceScanner";
import TeacherAttendanceScanner from "./pages/Attendance/TeacherAttendanceScanner";
import StaffAttendanceScannerGeolocated from "./pages/Attendance/StaffAttendanceScannerGeolocated";
import AttendanceReports from "./pages/Attendance/AttendanceReports";
import ManualAttendance from "./pages/ManualAttendance";
import TeacherDetailedStats from "./pages/Teachers/TeacherDetailedStats";
import ParentNotifications from './pages/Admin/ParentNotifications';

// Supervisor Management
import SupervisorStatus from "./pages/SupervisorManagement/SupervisorStatus";

// Search
import Search from "./pages/Search";

// Card Generator
import CardGenerator from "./pages/CardGenerator/CardGenerator";
import BulkStaffCards from "./pages/BulkStaffCards/BulkStaffCards";
import TestAPIEndpoints from "./components/TestAPIEndpoints";

// Stats
import Stats from "./pages/Stats";

// Inventory
import InventoryModule from "./pages/Inventory/InventoryModule";
import InventoryModuleSimple from "./pages/Inventory/InventoryModuleSimple";
import InventoryModuleStable from "./pages/Inventory/InventoryModuleStable";
import InventoryDebug from "./pages/Inventory/InventoryDebug";
import InventorySimplest from "./pages/Inventory/InventorySimplest";
import InventoryFull from "./pages/Inventory/InventoryFull";
import TestInventory from "./pages/Inventory/TestInventory";

// Documents
import DocumentsManager from "./pages/Documents/DocumentsManager";

// Bus Management
import BusSettings from "./pages/Bus/BusSettings";
import BusSubscriptions from "./pages/Bus/BusSubscriptions";
import BusSubscribers from "./pages/Bus/BusSubscribers";
import BusTicketGeneration from "./pages/Bus/BusTicketGeneration";
import BusTicketSales from "./pages/Bus/BusTicketSales";
import BusDailyReport from "./pages/Bus/BusDailyReport";

// Staff Attendance
import StaffAttendanceManagement from "./pages/Staff/StaffAttendanceManagement";
import StaffDailyAttendance from "./pages/Staff/StaffDailyAttendance";
import VacataireAttendanceReport from "./pages/Staff/VacataireAttendanceReport";

// Demandes d'Explication
import DemandesExplication from "./pages/comptables/DemandesExplication";
import NouvelleDemande from "./pages/comptables/NouvelleDemande";

// Academic Periods
import AcademicPeriodsManagement from "./pages/AcademicPeriods/AcademicPeriodsManagement";

// Grading Scales
import GradingScales from "./pages/GradingScales/GradingScales";

// Admin Trimester & Sequence Management
import TrimesterSequenceManagement from "./pages/Admin/TrimesterSequenceManagement";
import BulletinManagement from "./pages/Admin/BulletinManagementNew";
import PVGeneration from "./pages/PV/PVGeneration";
import MarkSheetGeneration from "./pages/MarkSheets/MarkSheetGeneration";
import SubjectGroups from "./pages/Admin/SubjectGroups";
import SubjectGroupsSettings from "./pages/Admin/SubjectGroupsSettings";

// Parent Pages
import ParentLogin from "./pages/Parent/ParentLogin";
import ParentDashboard from "./pages/Parent/ParentDashboard";


// Components
import Sidebar from "./components/Sidebar";
import TopBar from "./components/TopBar";

// Composant interne qui utilise les hooks d'auth
const AppContent = () => {
  const { isAuthenticated, user } = useAuth();
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [isMobile, setIsMobile] = useState(window.innerWidth <= 768);
  const [sidebarOpen, setSidebarOpen] = useState(false);

  useEffect(() => {
    const handleResize = () => {
      setIsMobile(window.innerWidth <= 768);
    };

    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  const handleSidebarToggle = () => {
    if (isMobile) {
      setSidebarOpen(!sidebarOpen);
    } else {
      setSidebarCollapsed(!sidebarCollapsed);
    }
  };

  return (
    <div className="app">
      <Router 
        future={{
          v7_startTransition: true,
          v7_relativeSplatPath: true
        }}
      >
        {isAuthenticated && (
          <Sidebar
            isCollapsed={sidebarCollapsed}
            onToggle={handleSidebarToggle}
            isOpen={sidebarOpen}
            setIsOpen={setSidebarOpen}
          />
        )}

        <div
          className="main-content"
          style={{
            marginLeft: isAuthenticated
              ? isMobile
                ? "0"
                : sidebarCollapsed
                ? "80px"
                : "280px"
              : "0",
            transition: "margin-left 0.3s ease",
          }}
        >
          {isAuthenticated && (
            <TopBar
              onSidebarToggle={handleSidebarToggle}
              showSidebarToggle={isMobile}
            />
          )}

          <div className="view animate-fade-in">
            <Routes>
              {/* Route publique - Login */}
              <Route
                path="/login"
                element={
                  <PublicRoute redirectPath="/">
                    <Login />
                  </PublicRoute>
                }
              />

              {/* Route de redirection basée sur le rôle */}
              <Route
                path="/"
                element={
                  <RoleBasedRedirect>
                    <Sections />
                  </RoleBasedRedirect>
                }
              />

              {/* Routes principales - accessibles à tous les utilisateurs connectés */}
              <Route
                path="/profile"
                element={
                  <ProtectedRoute>
                    <UserProfile />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/students/series/:seriesId"
                element={
                  <ProtectedRoute requiredRoles={['admin', 'principal', 'secretaire', 'accountant', 'comptable_superieur']}>
                    <SeriesStudents />
                  </ProtectedRoute>
                }
              />

              {/* Route générale pour la gestion des élèves par le principal */}
              <Route
                path="/students"
                element={
                  <AdminRoute>
                    <StudentsOverview />
                  </AdminRoute>
                }
              />

              {/* Route pour les transferts d'élèves */}
              <Route
                path="/student-transfers"
                element={
                  <ProtectedRoute requiredRoles={['admin', 'principal', 'secretaire']}>
                    <StudentTransfers />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/my-needs"
                element={
                  <ProtectedRoute>
                    <MyNeeds />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/search"
                element={
                  <ProtectedRoute>
                    <Search />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/stats"
                element={
                  <ProtectedRoute>
                    <Stats />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/inventory"
                element={
                  <AccountantRoute>
                    <InventoryFull />
                  </AccountantRoute>
                }
              />

              <Route
                path="/documents"
                element={
                  <ProtectedRoute>
                    <DocumentsManager />
                  </ProtectedRoute>
                }
              />
              
              {/* Route pour la gestion des présences du personnel - Secrétaires et Comptables Supérieurs */}
              <Route
                path="/staff-attendance-management"
                element={
                  <ProtectedRoute requiredRoles={['secretaire', 'comptable_superieur', 'accountant', 'admin']}>
                    <StaffAttendanceManagement />
                  </ProtectedRoute>
                }
              />

              {/* Route pour le suivi des présences élèves - Comptables */}
              <Route
                path="/student-attendance-tracking"
                element={
                  <AccountantRoute>
                    <StudentAttendanceTracking />
                  </AccountantRoute>
                }
              />

              {/* Route pour le rapport de présence du personnel - Comptables et Bibliothécaires */}
              <Route
                path="/staff-attendance-report"
                element={
                  <ProtectedRoute requiredRoles={['accountant', 'comptable_superieur', 'admin', 'bibliothecaire', 'surveillant_general', 'surveillant_secteur']}>
                    <StaffAttendanceReportCompt />
                  </ProtectedRoute>
                }
              />

              {/* Route pour le suivi des présences personnel - Comptables et Bibliothécaires */}
              <Route
                path="/staff-daily-attendance"
                element={
                  <ProtectedRoute requiredRoles={['accountant', 'comptable_superieur', 'admin', 'bibliothecaire']}>
                    <StaffDailyAttendance />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/test-inventory"
                element={
                  <AdminRoute>
                    <TestInventory />
                  </AdminRoute>
                }
              />

              <Route
                path="/attendance"
                element={
                  <ProtectedRoute requiredRoles={['bibliothecaire', 'admin', 'surveillant_general', 'surveillant_secteur']}>
                    <AttendanceScanner />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/manual-attendance"
                element={
                  <ProtectedRoute requiredRoles={['bibliothecaire', 'admin', 'accountant', 'comptable_superieur', 'surveillant_general', 'surveillant_secteur']}>
                    <ManualAttendance />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/teacher-attendance-scanner"
                element={
                  <ProtectedRoute requiredRoles={['bibliothecaire', 'admin']}>
                    <TeacherAttendanceScanner />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/staff-attendance-scanner"
                element={
                  <ProtectedRoute requiredRoles={['admin', 'bibliothecaire', 'surveillant_general', 'surveillant_secteur']}>
                    <StaffAttendanceScannerGeolocated />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/teacher-detailed-stats"
                element={
                  <ProtectedRoute requiredRoles={['bibliothecaire', 'admin']}>
                    <TeacherDetailedStats />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/attendance-reports"
                element={
                  <ProtectedRoute requiredRoles={['bibliothecaire', 'admin', 'accountant', 'comptable_superieur', 'surveillant_general', 'surveillant_secteur']}>
                    <AttendanceReports />
                  </ProtectedRoute>
                }
              />

              {/* Routes pour administrateurs uniquement */}
              <Route
                path="/admin/dashboard"
                element={
                  <AdminRoute>
                    <AdminDashboard />
                  </AdminRoute>
                }
              />

              {/* Routes pour le principal uniquement */}
              <Route
                path="/principal/dashboard"
                element={
                  <ProtectedRoute requiredRoles={["principal"]}>
                    <PrincipalDashboard />
                  </ProtectedRoute>
                }
              />
              <Route
                path="/sections"
                element={
                  <AdminRoute>
                    <Sections />
                  </AdminRoute>
                }
              />

              <Route
                path="/levels"
                element={
                  <AdminRoute>
                    <Levels />
                  </AdminRoute>
                }
              />

              <Route
                path="/academic-periods"
                element={
                  <ProtectedRoute requiredRoles={['admin', 'principal', 'secretaire', 'comptable_superieur']}>
                    <AcademicPeriodsManagement />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/grading-scales"
                element={
                  <ProtectedRoute requiredRoles={['admin', 'principal', 'secretaire', 'comptable_superieur']}>
                    <GradingScales />
                  </ProtectedRoute>
                }
              />
              <Route
                path="/admin/trimesters-sequences"
                element={
                  <ProtectedRoute requiredRoles={['admin', 'principal', 'secretaire', 'comptable_superieur']}>
                    <TrimesterSequenceManagement />
                  </ProtectedRoute>
                }
              />
              <Route
                path="/admin/bulletins"
                element={
                  <ProtectedRoute requiredRoles={['admin', 'principal', 'secretaire', 'comptable_superieur']}>
                    <BulletinManagement />
                  </ProtectedRoute>
                }
              />
              <Route
                path="/admin/pv"
                element={
                  <ProtectedRoute requiredRoles={['admin', 'principal', 'secretaire', 'comptable_superieur']}>
                    <PVGeneration />
                  </ProtectedRoute>
                }
              />
              <Route
                path="/admin/mark-sheets"
                element={
                  <ProtectedRoute requiredRoles={['admin', 'principal', 'secretaire', 'accountant', 'comptable_superieur']}>
                    <MarkSheetGeneration />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/admin/subject-groups"
                element={
                  <ProtectedRoute requiredRoles={['admin', 'principal', 'secretaire']}>
                    <SubjectGroups />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/admin/subject-groups-settings"
                element={
                  <ProtectedRoute requiredRoles={['admin', 'principal']}>
                    <SubjectGroupsSettings />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/admin/parent-notifications"
                element={
                  <ProtectedRoute requiredRoles={['admin', 'secretaire']}>
                    <ParentNotifications />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/school-classes"
                element={
                  <AdminRoute>
                    <SchoolClasses />
                  </AdminRoute>
                }
              />

              <Route
                path="/payment-tranches"
                element={
                  <AdminRoute>
                    <PaymentTranches />
                  </AdminRoute>
                }
              />

              <Route
                path="/card-generator"
                element={
                  <AdminRoute>
                    <CardGenerator />
                  </AdminRoute>
                }
              />
              
              <Route
                path="/bulk-staff-cards"
                element={
                  <AdminRoute>
                    <BulkStaffCards />
                  </AdminRoute>
                }
              />
              
              <Route
                path="/test-api-endpoints"
                element={
                  <AdminRoute>
                    <TestAPIEndpoints />
                  </AdminRoute>
                }
              />
              <Route
                path="/settings"
                element={
                  <AdminRoute>
                    <Settings />
                  </AdminRoute>
                }
              />

              <Route
                path="/geolocation-zones"
                element={
                  <AdminRoute>
                    <GeolocationZoneSettingsV2 />
                  </AdminRoute>
                }
              />

              <Route
                path="/school-years"
                element={
                  <AdminRoute>
                    <SchoolYears />
                  </AdminRoute>
                }
              />

              <Route

                path="/user-management"
                element={
                  <AdminRoute>
                    <UserManagement />
                  </AdminRoute>
                }
              />

              <Route
                path="/supervisor-assignments"
                element={
                  <AdminRoute>
                    <SupervisorStatus />
                  </AdminRoute>
                }
              />

              <Route
                path="/subjects"
                element={
                  <ProtectedRoute requiredRoles={['admin', 'secretaire']}>
                    <Subjects />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/series-subject-configuration"
                element={
                  <ProtectedRoute requiredRoles={['admin', 'secretaire']}>
                    <SeriesSubjectConfiguration />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/teachers"
                element={
                  <ProtectedRoute requiredRoles={['admin', 'principal', 'secretaire', 'comptable_superieur']}>
                    <Teachers />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/teacher-assignments"
                element={
                  <ProtectedRoute requiredRoles={['admin', 'principal', 'secretaire', 'comptable_superieur']}>
                    <TeacherAssignmentManagement />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/teacher-assignments-old"
                element={
                  <AdminRoute>
                    <TeacherAssignments />
                  </AdminRoute>
                }
              />

              <Route
                path="/departments"
                element={
                  <AdminRoute>
                    <DepartmentManagement />
                  </AdminRoute>
                }
              />

              <Route
                path="/needs-management"
                element={
                  <NeedsManagementRoute>
                    <NeedsManagement />
                  </NeedsManagementRoute>
                }
              />

              {/* Routes pour comptables et administrateurs */}
              <Route
                path="/students-comp"
                element={
                  <AccountantRoute>
                    <StudentsComp />
                  </AccountantRoute>
                }
              />

              <Route
                path="/class-comp"
                element={
                  <AccountantRoute>
                    <ClassCompt />
                  </AccountantRoute>
                }
              />

              <Route
                path="/class-comp/:id"
                element={
                  <AccountantRoute>
                    <StudentsByClass />
                  </AccountantRoute>
                }
              />


              <Route
                path="/params-comp"
                element={
                  <AccountantRoute>
                    <ParamsCompt />
                  </AccountantRoute>
                }
              />

              <Route
                path="/payment-reports"
                element={
                  <AccountantRoute>
                    <PaymentReports />
                  </AccountantRoute>
                }
              />
              <Route
                path="/reports"
                element={
                  <AccountantRoute>
                    <Reports />
                  </AccountantRoute>
                }
              />
              <Route
                path="/reports/school-fee-payment-details"
                element={
                  <AccountantRoute>
                    <SchoolFeePaymentDetails />
                  </AccountantRoute>
                }
              />
              <Route
                path="/reports/detailed-collection"
                element={
                  <AccountantRoute>
                    <DetailedCollectionReport />
                  </AccountantRoute>
                }
              />
              <Route
                path="/reports/class-school-fees"
                element={
                  <AccountantRoute>
                    <ClassSchoolFeesReport />
                  </AccountantRoute>
                }
              />
              <Route
                path="/reports/recovery-status"
                element={
                  <AccountantRoute>
                    <RecoveryStatus />
                  </AccountantRoute>
                }
              />
              <Route
                path="/reports/school-certificates"
                element={
                  <AccountantRoute>
                    <SchoolCertificates />
                  </AccountantRoute>
                }
              />
              <Route
                path="/reports/class-fees-sheet"
                element={
                  <ProtectedRoute requiredRoles={['admin', 'accountant', 'comptable_superieur', 'principal', 'secretaire']}>
                    <ClassFeesSheet />
                  </ProtectedRoute>
                }
              />
              <Route
                path="/reports/staff-attendance-report"
                element={
                  <AdminRoute>
                    <StaffAttendanceReport />
                  </AdminRoute>
                }
              />
              <Route
                path="/reports/vacataire-attendance"
                element={
                  <ProtectedRoute requiredRoles={['admin', 'principal', 'comptable_superieur', 'accountant']}>
                    <VacataireAttendanceReport />
                  </ProtectedRoute>
                }
              />

              {/* Routes pour les D.E (Demandes d'Explication) - Comptables uniquement */}
              <Route
                path="/demandes-explication"
                element={
                  <AccountantRoute>
                    <DemandesExplication />
                  </AccountantRoute>
                }
              />
              <Route
                path="/demandes-explication/nouvelle"
                element={
                  <AccountantRoute>
                    <NouvelleDemande />
                  </AccountantRoute>
                }
              />

              {/* Routes pour les D.E - Personnel (enseignants, etc.) */}
              <Route
                path="/mes-demandes-explication"
                element={
                  <ProtectedRoute>
                    <DemandesExplication />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/student-payment/:studentId"
                element={
                  <AccountantRoute>
                    <StudentPayment />
                  </AccountantRoute>
                }
              />

              {/* Routes pour les frais de dossiers */}
              <Route
                path="/payments/documentary-fees"
                element={
                  <AccountantRoute>
                    <DocumentaryFees />
                  </AccountantRoute>
                }
              />
              <Route
                path="/payments/documentary-fees/create"
                element={
                  <AccountantRoute>
                    <CreateDocumentaryFee />
                  </AccountantRoute>
                }
              />
              <Route
                path="/payments/documentary-fees/:id"
                element={
                  <AccountantRoute>
                    <DocumentaryFeeDetails />
                  </AccountantRoute>
                }
              />
              <Route
                path="/payments/documentary-fees/:id/edit"
                element={
                  <AccountantRoute>
                    <CreateDocumentaryFee />
                  </AccountantRoute>
                }
              />
              <Route
                path="/payment-management"
                element={
                  <ProtectedRoute requiredRoles={['comptable_superieur', 'accountant']}>
                    <PaymentManagement />
                  </ProtectedRoute>
                }
              />

              {/* Routes Bus - Transport */}
              <Route
                path="/bus/settings"
                element={
                  <AccountantRoute>
                    <BusSettings />
                  </AccountantRoute>
                }
              />
              <Route
                path="/bus/subscriptions"
                element={
                  <AccountantRoute>
                    <BusSubscriptions />
                  </AccountantRoute>
                }
              />
              <Route
                path="/bus/subscribers"
                element={
                  <AccountantRoute>
                    <BusSubscribers />
                  </AccountantRoute>
                }
              />
              <Route
                path="/bus/ticket-generation"
                element={
                  <AccountantRoute>
                    <BusTicketGeneration />
                  </AccountantRoute>
                }
              />
              <Route
                path="/bus/ticket-sales"
                element={
                  <AccountantRoute>
                    <BusTicketSales />
                  </AccountantRoute>
                }
              />
              <Route
                path="/bus/daily-report"
                element={
                  <AccountantRoute>
                    <BusDailyReport />
                  </AccountantRoute>
                }
              />

              {/* Routes Enseignants */}
              <Route
                path="/teacher/dashboard"
                element={
                  <TeacherRoute>
                    <TeacherDashboard />
                  </TeacherRoute>
                }
              />
              
              <Route
                path="/teacher/class/:classId/students"
                element={
                  <TeacherRoute>
                    <TeacherClassStudents />
                  </TeacherRoute>
                }
              />
              
              <Route
                path="/sequences"
                element={
                  <TeacherRoute>
                    <Sequences />
                  </TeacherRoute>
                }
              />
              
              <Route
                path="/trimesters"
                element={
                  <TeacherRoute>
                    <Trimesters />
                  </TeacherRoute>
                }
              />
              
              <Route
                path="/trimesters/:trimesterId/ds-details"
                element={
                  <TeacherRoute>
                    <DSDetails />
                  </TeacherRoute>
                }
              />
              
              <Route
                path="/teacher/sequences/:sequenceId/subjects"
                element={
                  <TeacherRoute>
                    <SequenceSubjects />
                  </TeacherRoute>
                }
              />
              
              <Route
                path="/teacher/sequences/:sequenceId/subjects/:subjectId/students"
                element={
                  <TeacherRoute>
                    <SubjectStudents />
                  </TeacherRoute>
                }
              />
              
              <Route
                path="/teacher/evaluations"
                element={
                  <TeacherRoute>
                    <Evaluations />
                  </TeacherRoute>
                }
              />
              
              <Route
                path="/teacher/evaluations/create"
                element={
                  <TeacherRoute>
                    <EvaluationCreate />
                  </TeacherRoute>
                }
              />
              
              <Route
                path="/teacher/evaluations/:evaluationId/grades"
                element={
                  <TeacherRoute>
                    <GradeEntry />
                  </TeacherRoute>
                }
              />

              <Route
                path="/teacher/competences"
                element={
                  <TeacherRoute>
                    <Competences />
                  </TeacherRoute>
                }
              />

              {/* Routes Parent - indépendantes du système d'auth principal */}
              <Route
                path="/parent/login"
                element={<ParentLogin />}
              />
              <Route
                path="/parent/dashboard"
                element={<ParentDashboard />}
              />

              {/* 404 pour les utilisateurs connectés */}
              <Route
                path="*"
                element={
                  <ProtectedRoute>
                    <Error404 />
                  </ProtectedRoute>
                }
              />
            </Routes>
          </div>
        </div>
      </Router>
    </div>
  );
};

// Composant App principal avec provider
function App() {
  return (
    <AppAuthProvider>
      <SchoolProvider>
        <ThemeProvider>
          <AppContent />
        </ThemeProvider>
      </SchoolProvider>
    </AppAuthProvider>
  );
}

export default App;
