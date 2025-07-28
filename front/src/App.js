import "bootstrap/dist/css/bootstrap.min.css";
import { useEffect, useState } from "react";
import { Route, BrowserRouter as Router, Routes } from "react-router-dom";
import "./App.css";

// Auth Components
import AppAuthProvider from "./components/AuthProvider";
import ProtectedRoute, {
  AccountantRoute,
  AdminRoute,
  PublicRoute,
  RoleBasedRedirect,
  TeacherRoute,
} from "./components/ProtectedRoute";
import { useAuth } from "./hooks/useAuth";

// Pages
import Class from "./pages/Class/Class";
import ClassBySection from "./pages/Class/ClassBySection";
import CreateClass from "./pages/Class/CreateClass";
import Docs from "./pages/Documentation";
import Error404 from "./pages/Error404";
import Login from "./pages/Login";
import PaymentTranches from "./pages/PaymentTranches";
import Params from "./pages/Profile/Params";
import SearchView from "./pages/Search";
import Sections from "./pages/Sections/Sections";
import Levels from "./pages/Levels/Levels";
import Settings from "./pages/Settings";
import Statistics from "./pages/Statistics.jsx";
import Student from "./pages/Students/Student";
import Teachers from "./pages/Teachers/Teachers";

// Comptable Pages
import ClassCompt from "./pages/comptables/Class";
import ParamsCompt from "./pages/comptables/Params";
import ReductFees from "./pages/comptables/ReductFees";
import StudentsComp from "./pages/comptables/Students";
import StudentsByClass from "./pages/comptables/StudentsByClass";
import TransfertStudent from "./pages/Students/TransfertStudent";

// Components
import Sidebar from "./components/Sidebar";
import TopBar from "./components/TopBar";

// Composant interne qui utilise les hooks d'auth
const AppContent = () => {
  const { isAuthenticated, user } = useAuth();
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [isMobile, setIsMobile] = useState(window.innerWidth <= 768);

  useEffect(() => {
    const handleResize = () => {
      setIsMobile(window.innerWidth <= 768);
    };

    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  const handleSidebarToggle = () => {
    setSidebarCollapsed(!sidebarCollapsed);
  };

  
  return (
    <div className="app">
      <Router>
        {isAuthenticated && (
          <Sidebar
            isCollapsed={sidebarCollapsed}
            onToggle={handleSidebarToggle}
          />
        )}

        <div 
          className="main-content" 
          style={{
            marginLeft: isAuthenticated ? (sidebarCollapsed && !isMobile ? '80px' : '280px') : '0',
            transition: 'margin-left 0.3s ease'
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
                path="/search"
                element={
                  <ProtectedRoute>
                    <SearchView />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/params"
                element={
                  <ProtectedRoute>
                    <Params />
                  </ProtectedRoute>
                }
              />

              <Route
                path="/docs"
                element={
                  <ProtectedRoute>
                    <Docs />
                  </ProtectedRoute>
                }
              />

              {/* Routes pour enseignants et administrateurs */}
              <Route
                path="/class"
                element={
                  <TeacherRoute>
                    <Class />
                  </TeacherRoute>
                }
              />

              <Route
                path="/classBySection/:name"
                element={
                  <TeacherRoute>
                    <ClassBySection />
                  </TeacherRoute>
                }
              />

              <Route
                path="/students/:id"
                element={
                  <TeacherRoute>
                    <Student />
                  </TeacherRoute>
                }
              />

              <Route
                path="/transfert/:id"
                element={
                  <TeacherRoute>
                    <TransfertStudent />
                  </TeacherRoute>
                }
              />

              <Route
                path="/stats"
                element={
                  <TeacherRoute>
                    <Statistics />
                  </TeacherRoute>
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
                path="/teachers"
                element={
                  <AdminRoute>
                    <Teachers />
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
                path="/classes/create"
                element={
                  <AdminRoute>
                    <CreateClass />
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
                path="/reduct-fees/:id"
                element={
                  <AccountantRoute>
                    <ReductFees />
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
      <AppContent />
    </AppAuthProvider>
  );
}

export default App;
