import React, { useState } from 'react';
import { AppProvider } from './contexts/AppContext';
import Navigation from './components/Navigation';
import Dashboard from './components/Dashboard';
import MachineManagement from './components/MachineManagement';
import ToolManagement from './components/ToolManagement';

function App() {
  const [currentView, setCurrentView] = useState('dashboard');

  const renderView = () => {
    switch (currentView) {
      case 'dashboard':
        return <Dashboard />;
      case 'machines':
        return <MachineManagement />;
      case 'tools':
        return <ToolManagement />;
      default:
        return <Dashboard />;
    }
  };

  return (
    <AppProvider>
      <div className="min-h-screen bg-gray-50">
        <Navigation currentView={currentView} setCurrentView={setCurrentView} />
        {renderView()}
      </div>
    </AppProvider>
  );
}

export default App;
