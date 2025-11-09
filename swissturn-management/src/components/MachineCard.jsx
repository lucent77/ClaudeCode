import React from 'react';
import { Play, Pause, AlertCircle, Wrench } from 'lucide-react';

const MachineCard = ({ machine }) => {
  const getStatusColor = (status) => {
    switch (status) {
      case 'running':
        return 'bg-green-100 text-green-800 border-green-300';
      case 'idle':
        return 'bg-yellow-100 text-yellow-800 border-yellow-300';
      case 'maintenance':
        return 'bg-red-100 text-red-800 border-red-300';
      default:
        return 'bg-gray-100 text-gray-800 border-gray-300';
    }
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'running':
        return <Play className="h-4 w-4" />;
      case 'idle':
        return <Pause className="h-4 w-4" />;
      case 'maintenance':
        return <Wrench className="h-4 w-4" />;
      default:
        return <AlertCircle className="h-4 w-4" />;
    }
  };

  const getStatusText = (status) => {
    switch (status) {
      case 'running':
        return '가동 중';
      case 'idle':
        return '대기';
      case 'maintenance':
        return '정비';
      default:
        return '알 수 없음';
    }
  };

  return (
    <div className="bg-white rounded-lg shadow border border-gray-200 p-4 hover:shadow-md transition-shadow">
      <div className="flex items-center justify-between mb-3">
        <h3 className="text-lg font-bold text-gray-900">{machine.name}</h3>
        <div className={`px-3 py-1 rounded-full text-xs font-medium border flex items-center gap-1 ${getStatusColor(machine.status)}`}>
          {getStatusIcon(machine.status)}
          {getStatusText(machine.status)}
        </div>
      </div>

      {/* Current Job */}
      <div className="mb-3">
        <p className="text-xs text-gray-500 mb-1">현재 작업</p>
        <p className="text-sm font-medium text-gray-900">
          {machine.currentJob || '작업 없음'}
        </p>
      </div>

      {/* Runtime Statistics */}
      <div className="grid grid-cols-2 gap-2 mb-3">
        <div>
          <p className="text-xs text-gray-500">가동 시간</p>
          <p className="text-sm font-semibold text-gray-900">{machine.runtime || 0}h</p>
        </div>
        <div>
          <p className="text-xs text-gray-500">다운타임</p>
          <p className="text-sm font-semibold text-gray-900">{machine.downtime || 0}h</p>
        </div>
      </div>

      {/* OEE */}
      <div>
        <div className="flex items-center justify-between mb-1">
          <p className="text-xs text-gray-500">OEE</p>
          <p className="text-xs font-semibold text-gray-900">{(machine.oee || 0).toFixed(1)}%</p>
        </div>
        <div className="w-full bg-gray-200 rounded-full h-2">
          <div
            className={`h-2 rounded-full transition-all ${
              machine.oee >= 85 ? 'bg-green-500' :
              machine.oee >= 60 ? 'bg-yellow-500' :
              'bg-red-500'
            }`}
            style={{ width: `${Math.min(100, machine.oee || 0)}%` }}
          />
        </div>
      </div>

      {/* Tools Count */}
      <div className="mt-3 pt-3 border-t border-gray-200">
        <p className="text-xs text-gray-500">
          장착된 공구: <span className="font-semibold text-gray-900">{machine.tools?.length || 0}개</span>
        </p>
      </div>
    </div>
  );
};

export default MachineCard;
