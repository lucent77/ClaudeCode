import React from 'react';
import { LayoutDashboard, Settings, Wrench, LogOut, User } from 'lucide-react';
import { useApp } from '../contexts/AppContext';

const Navigation = ({ currentView, setCurrentView }) => {
  const { user } = useApp();

  const navItems = [
    { id: 'dashboard', label: '대시보드', icon: LayoutDashboard },
    { id: 'machines', label: '장비 관리', icon: Settings },
    { id: 'tools', label: '공구 관리', icon: Wrench },
  ];

  return (
    <nav className="bg-white shadow-sm border-b border-gray-200">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          {/* Logo and Nav Items */}
          <div className="flex items-center gap-8">
            <div className="flex-shrink-0">
              <h1 className="text-xl font-bold text-blue-600">Swissturn</h1>
            </div>
            <div className="flex gap-1">
              {navItems.map(item => {
                const Icon = item.icon;
                const isActive = currentView === item.id;

                return (
                  <button
                    key={item.id}
                    onClick={() => setCurrentView(item.id)}
                    className={`flex items-center gap-2 px-4 py-2 rounded-lg transition-colors ${
                      isActive
                        ? 'bg-blue-50 text-blue-700 font-medium'
                        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                    }`}
                  >
                    <Icon className="h-5 w-5" />
                    <span className="text-sm">{item.label}</span>
                  </button>
                );
              })}
            </div>
          </div>

          {/* User Info */}
          <div className="flex items-center gap-4">
            <div className="flex items-center gap-2 px-3 py-1.5 bg-gray-100 rounded-lg">
              <User className="h-4 w-4 text-gray-600" />
              <span className="text-sm font-medium text-gray-900">{user.name}</span>
              <span className={`text-xs px-2 py-0.5 rounded-full ${
                user.role === 'admin' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700'
              }`}>
                {user.role === 'admin' ? '관리자' : '작업자'}
              </span>
            </div>
          </div>
        </div>
      </div>
    </nav>
  );
};

export default Navigation;
