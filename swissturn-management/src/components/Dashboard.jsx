import React from 'react';
import { useApp } from '../contexts/AppContext';
import MachineCard from './MachineCard';
import ToolOverview from './ToolOverview';
import ProductionStats from './ProductionStats';
import { Activity, Package, AlertTriangle, CheckCircle } from 'lucide-react';

const Dashboard = () => {
  const { machines, tools, productionData } = useApp();

  // Calculate statistics
  const runningMachines = machines.filter(m => m.status === 'running').length;
  const totalTools = tools.length;
  const criticalTools = tools.filter(t => {
    const remaining = ((t.lifespanLimit - t.currentUsage) / t.lifespanLimit) * 100;
    return remaining < 20;
  }).length;

  const avgOEE = machines.reduce((sum, m) => sum + (m.oee || 0), 0) / machines.length;

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white shadow-sm border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <h1 className="text-2xl font-bold text-gray-900">Swissturn 생산 관리 시스템</h1>
          <p className="text-sm text-gray-600 mt-1">통합 대시보드 - 실시간 모니터링</p>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        {/* Key Metrics */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <Activity className="h-8 w-8 text-green-600" />
              </div>
              <div className="ml-4 flex-1">
                <p className="text-sm font-medium text-gray-600">가동 중인 장비</p>
                <p className="text-2xl font-bold text-gray-900">{runningMachines}/{machines.length}</p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <Package className="h-8 w-8 text-blue-600" />
              </div>
              <div className="ml-4 flex-1">
                <p className="text-sm font-medium text-gray-600">전체 공구</p>
                <p className="text-2xl font-bold text-gray-900">{totalTools}</p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <AlertTriangle className="h-8 w-8 text-red-600" />
              </div>
              <div className="ml-4 flex-1">
                <p className="text-sm font-medium text-gray-600">교체 필요 공구</p>
                <p className="text-2xl font-bold text-gray-900">{criticalTools}</p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <CheckCircle className="h-8 w-8 text-purple-600" />
              </div>
              <div className="ml-4 flex-1">
                <p className="text-sm font-medium text-gray-600">평균 OEE</p>
                <p className="text-2xl font-bold text-gray-900">{avgOEE.toFixed(1)}%</p>
              </div>
            </div>
          </div>
        </div>

        {/* Machine Status Grid */}
        <div className="mb-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">장비 현황</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {machines.map(machine => (
              <MachineCard key={machine.id} machine={machine} />
            ))}
          </div>
        </div>

        {/* Tool Overview and Production Stats */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <ToolOverview />
          <ProductionStats />
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
