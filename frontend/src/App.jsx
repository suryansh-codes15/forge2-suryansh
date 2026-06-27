import { BrowserRouter, Routes, Route, Navigate, NavLink, useNavigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './AuthContext';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import DashboardPage from './pages/DashboardPage';
import TicketsPage from './pages/TicketsPage';
import TicketDetailPage from './pages/TicketDetailPage';
import NewTicketPage from './pages/NewTicketPage';
import './index.css';

function Sidebar() {
  const { user, organization, signOut } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    try { const { logout } = await import('./api'); await logout(); } catch {}
    signOut();
    navigate('/login');
  };

  return (
    <aside className="sidebar">
      <div className="sidebar-logo">
        <h1>PulseDesk</h1>
        <span>{organization?.name || 'Help Desk'}</span>
      </div>
      <nav className="sidebar-nav">
        <NavLink to="/dashboard" className={({isActive}) => `nav-link${isActive?' active':''}`}>
          <span className="nav-icon">📊</span> Dashboard
        </NavLink>
        <NavLink to="/tickets" className={({isActive}) => `nav-link${isActive?' active':''}`}>
          <span className="nav-icon">🎫</span> Tickets
        </NavLink>
        <NavLink to="/tickets/new" className={({isActive}) => `nav-link${isActive?' active':''}`}>
          <span className="nav-icon">✏️</span> New Ticket
        </NavLink>
      </nav>
      <div className="sidebar-footer">
        <strong>{user?.name}</strong>
        <span style={{textTransform:'capitalize'}}>{user?.role}</span>
        <button className="btn btn-ghost btn-sm" style={{marginTop:'12px',width:'100%'}} onClick={handleLogout}>
          Sign Out
        </button>
      </div>
    </aside>
  );
}

function ProtectedRoute({ children }) {
  const { user, loading } = useAuth();
  if (loading) return <div className="spinner" />;
  if (!user) return <Navigate to="/login" replace />;
  return children;
}

function AppLayout({ children }) {
  return (
    <div className="layout">
      <Sidebar />
      <main className="main-content">{children}</main>
    </div>
  );
}

export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />
          <Route path="/" element={<Navigate to="/dashboard" replace />} />
          <Route path="/dashboard" element={
            <ProtectedRoute>
              <AppLayout><DashboardPage /></AppLayout>
            </ProtectedRoute>
          } />
          <Route path="/tickets" element={
            <ProtectedRoute>
              <AppLayout><TicketsPage /></AppLayout>
            </ProtectedRoute>
          } />
          <Route path="/tickets/new" element={
            <ProtectedRoute>
              <AppLayout><NewTicketPage /></AppLayout>
            </ProtectedRoute>
          } />
          <Route path="/tickets/:id" element={
            <ProtectedRoute>
              <AppLayout><TicketDetailPage /></AppLayout>
            </ProtectedRoute>
          } />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}
