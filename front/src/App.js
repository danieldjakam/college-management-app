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
import Settings from "./pages/Settings";
import UserProfile from "./pages/Profile/UserProfile";
import SeriesStudents from "./pages/Students/SeriesStudents";

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

// Reports
import Reports from "./pages/Reports";
import SchoolFeePaymentDetails from "./pages/Reports/SchoolFeePaymentDetails";
import DetailedCollectionReport from "./pages/Reports/DetailedCollectionReport";
import ClassSchoolFeesReport from "./pages/Reports/ClassSchoolFeesReport";
import RecoveryStatus from "./pages/Reports/RecoveryStatus";
import SchoolCertificates from "./pages/Reports/SchoolCertificates";
import StaffAttendanceReport from "./pages/Reports/StaffAttendanceReport";


// User Management
import UserManagement from "./pages/UserManagement";

// Subjects & Teachers
import Subjects from "./pages/Subjects/Subjects";
import SeriesSubjectConfiguration from "./pages/Subjects/SeriesSubjectConfiguration";
import Teachers from "./pages/Teachers/Teachers";
import TeacherAssignments from "./pages/Teachers/TeacherAssignments";
import TeacherAssignmentManagement from "./pages/Teachers/TeacherAssignmentManagement";

// Departments
import DepartmentManagement from "./pages/Departments/DepartmentManagement";

// Needs
import MyNeeds from "./pages/Needs/MyNeeds";
import NeedsManagement from "./pages/Needs/NeedsManagement";

// Attendance
import AttendanceScanner from "./pages/Attendance/AttendanceScanner";
import TeacherAttendanceScanner from "./pages/Attendance/TeacherAttendanceScanner";
import StaffAttendanceScanner from "./pages/Attendance/StaffAttendanceScanner";
import AttendanceReports from "./pages/Attendance/AttendanceReports";
import TeacherDetailedStats from "./pages/Teachers/TeacherDetailedStats";

// Supervisor Management
import SupervisorStatus from "./pages/SupervisorManagement/SupervisorStatus";

// Search
import Search from "./pages/Search";

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

// Staff Attendance
import StaffAttendanceManagement from "./pages/Staff/StaffAttendanceManagement";
import StaffDailyAttendance from "./pages/Staff/StaffDailyAttendance";

// Demandes d'Explication
import DemandesExplication from "./pages/comptables/DemandesExplication";
import NouvelleDemande from "./pages/comptables/NouvelleDemande";


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
                  <ProtectedRoute>
                    <SeriesStudents />
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
                  <ProtectedRoute requiredRoles={['accountant', 'comptable_superieur', 'admin', 'bibliothecaire']}>
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
                  <ProtectedRoute requiredRoles={['bibliothecaire', 'admin']}>
                    <AttendanceScanner />
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
                  <ProtectedRoute requiredRoles={['admin', 'bibliothecaire']}>
                    <StaffAttendanceScanner />
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
                  <ProtectedRoute requiredRoles={['bibliothecaire', 'admin', 'accountant', 'comptable_superieur']}>
                    <AttendanceReports />
                  </ProtectedRoute>
                }
              />

              {/* Routes pour administrateurs uniquement */}
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
                path="/settings"
                element={
                  <AdminRoute>
                    <Settings />
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
                  <AdminRoute>
                    <Subjects />
                  </AdminRoute>
                }
              />

              <Route
                path="/series-subject-configuration"
                element={
                  <AdminRoute>
                    <SeriesSubjectConfiguration />
                  </AdminRoute>
                }
              />

              <Route
                path="/teachers"
                element={
                  <AdminRoute>
                    <Teachers />
                  </AdminRoute>
                }
              />

              <Route
                path="/teacher-assignments"
                element={
                  <AdminRoute>
                    <TeacherAssignmentManagement />
                  </AdminRoute>
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
                path="/reports/staff-attendance-report"
                element={
                  <AccountantRoute>
                    <StaffAttendanceReport />
                  </AccountantRoute>
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
