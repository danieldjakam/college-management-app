import React, { useState, useEffect } from 'react'
import { Route, BrowserRouter as Router, Routes } from 'react-router-dom';
import './App.css';
import 'bootstrap/dist/css/bootstrap.min.css'

// Pages
import Class from './pages/Class/Class';
import Student from './pages/Students/Student';
import Teachers from './pages/Teachers/Teachers';
import Login from './pages/Login';
import SearchView from './pages/Search';
import Params from './pages/Profile/Params';
import Error404 from './pages/Error404';
import Settings from './pages/Settings';
import Home from './pages/Home';
import ClassBySection from './pages/Class/ClassBySection';
import Statistics from './pages/Statistics.jsx';
import Docs from './pages/Documentation';


// Comptable Pages
import StudentsComp from './pages/comptables/Students';
import ClassCompt from './pages/comptables/Class';
import ParamsCompt from './pages/comptables/Params';
import StudentsByClass from './pages/comptables/StudentsByClass';
import ReductFees from './pages/comptables/ReductFees';
import TransfertStudent from './pages/Students/TransfertStudent';

// Components
import Sidebar from './components/Sidebar';
import TopBar from './components/TopBar';

function App() {
  const [user, setUser] = useState(!!sessionStorage.user);
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [isMobile, setIsMobile] = useState(window.innerWidth <= 768);

  useEffect(() => {
    const handleResize = () => {
      setIsMobile(window.innerWidth <= 768);
    };

    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, []);

  useEffect(() => {
    // Update user state when sessionStorage changes
    const checkUser = () => {
      setUser(!!sessionStorage.user);
    };

    // Listen for storage changes
    window.addEventListener('storage', checkUser);
    return () => window.removeEventListener('storage', checkUser);
  }, []);

  const handleSidebarToggle = () => {
    setSidebarCollapsed(!sidebarCollapsed);
  };

  return (
    <div className="app">
      <Router>
        {user && (
          <Sidebar 
            isCollapsed={sidebarCollapsed} 
            onToggle={handleSidebarToggle}
          />
        )}
        
        <div className="main-content">
          {user && (
            <TopBar 
              user={user} 
              onSidebarToggle={handleSidebarToggle}
              showSidebarToggle={isMobile}
            />
          )}
          
          <div className="view animate-fade-in">
            <Routes>
              {user ? (
                <>
                  {/* Main Routes */}
                  <Route path='/' element={<Home/>} />
                  <Route path='/class' element={<Class/>} /> 
                  <Route path='/classBySection/:name' element={<ClassBySection />} /> 
                  <Route path='/students/:id' element={<Student/>} />
                  <Route path='/transfert/:id' element={<TransfertStudent/>} />
                  <Route path='/teachers' element={<Teachers/>} />
                  <Route path='/search' element={<SearchView/>}/>
                  <Route path='/params' element={<Params/>}/>
                  <Route path='/settings' element={<Settings/>}/>
                  <Route path='/stats' element={<Statistics/>}/>
                  <Route path='/docs' element={<Docs />} />

                  {/* Comptable Routes */}
                  <Route path='/students-comp' element={<StudentsComp/>} />
                  <Route path='/class-comp' element={<ClassCompt/>} />
                  <Route path='/class-comp/:id' element={<StudentsByClass/>} />
                  <Route path='/reduct-fees/:id' element={<ReductFees/>} />
                  <Route path='/params-comp' element={<ParamsCompt/>} />

                  {/* 404 */}
                  <Route path='*' element={<Error404/>} />
                </>
              ) : (
                <>
                  <Route path='/login' element={<Login setUser={setUser}/>} />
                  <Route path='*' element={<Login setUser={setUser} />} />
                </>
              )}
            </Routes>
          </div>
        </div>
      </Router>
    </div>
  );
}

export default App;