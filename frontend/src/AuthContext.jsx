import { createContext, useContext, useState, useEffect } from 'react';
import { me } from './api';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser]             = useState(null);
  const [organization, setOrganization] = useState(null);
  const [loading, setLoading]       = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('token');
    if (token) {
      me().then(res => {
        setUser(res.data.user);
        setOrganization(res.data.organization);
      }).catch(() => {
        localStorage.removeItem('token');
      }).finally(() => setLoading(false));
    } else {
      setLoading(false);
    }
  }, []);

  const signIn = (token, userData, orgData) => {
    localStorage.setItem('token', token);
    setUser(userData);
    setOrganization(orgData);
  };

  const signOut = () => {
    localStorage.removeItem('token');
    setUser(null);
    setOrganization(null);
  };

  return (
    <AuthContext.Provider value={{ user, organization, loading, signIn, signOut }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => useContext(AuthContext);
