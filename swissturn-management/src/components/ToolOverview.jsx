import React from 'react';
import { useApp } from '../contexts/AppContext';
import { AlertTriangle, CheckCircle, AlertCircle } from 'lucide-react';
import { calculateToolLifeRemaining, getToolStatus } from '../utils/calculations';

const ToolOverview = () => {
  const { tools } = useApp();

  // Categorize tools by status
  const toolsByStatus = {
    good: [],
    warning: [],
    critical: []
  };

  tools.forEach(tool => {
    const remaining = calculateToolLifeRemaining(tool.currentUsage, tool.lifespanLimit);
    const status = getToolStatus(remaining);
    toolsByStatus[status].push({ ...tool, remaining });
  });

  return (
    <div className="bg-white rounded-lg shadow p-6">
      <h2 className="text-lg font-semibold text-gray-900 mb-4">공구 현황</h2>

      {/* Summary Cards */}
      <div className="grid grid-cols-3 gap-3 mb-4">
        <div className="bg-green-50 border border-green-200 rounded-lg p-3">
          <div className="flex items-center gap-2 mb-1">
            <CheckCircle className="h-4 w-4 text-green-600" />
            <p className="text-xs font-medium text-green-900">정상</p>
          </div>
          <p className="text-xl font-bold text-green-900">{toolsByStatus.good.length}</p>
        </div>

        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
          <div className="flex items-center gap-2 mb-1">
            <AlertCircle className="h-4 w-4 text-yellow-600" />
            <p className="text-xs font-medium text-yellow-900">주의</p>
          </div>
          <p className="text-xl font-bold text-yellow-900">{toolsByStatus.warning.length}</p>
        </div>

        <div className="bg-red-50 border border-red-200 rounded-lg p-3">
          <div className="flex items-center gap-2 mb-1">
            <AlertTriangle className="h-4 w-4 text-red-600" />
            <p className="text-xs font-medium text-red-900">위험</p>
          </div>
          <p className="text-xl font-bold text-red-900">{toolsByStatus.critical.length}</p>
        </div>
      </div>

      {/* Critical Tools List */}
      {toolsByStatus.critical.length > 0 && (
        <div className="mb-4">
          <h3 className="text-sm font-semibold text-gray-900 mb-2 flex items-center gap-2">
            <AlertTriangle className="h-4 w-4 text-red-600" />
            교체 필요 공구
          </h3>
          <div className="space-y-2">
            {toolsByStatus.critical.slice(0, 3).map(tool => (
              <div key={tool.id} className="bg-red-50 border border-red-200 rounded p-2">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium text-gray-900">{tool.code}</p>
                    <p className="text-xs text-gray-600">{tool.name}</p>
                  </div>
                  <div className="text-right">
                    <p className="text-sm font-bold text-red-600">{tool.remaining.toFixed(0)}%</p>
                    <p className="text-xs text-gray-500">남은 수명</p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Warning Tools List */}
      {toolsByStatus.warning.length > 0 && (
        <div>
          <h3 className="text-sm font-semibold text-gray-900 mb-2 flex items-center gap-2">
            <AlertCircle className="h-4 w-4 text-yellow-600" />
            주의 공구
          </h3>
          <div className="space-y-2">
            {toolsByStatus.warning.slice(0, 3).map(tool => (
              <div key={tool.id} className="bg-yellow-50 border border-yellow-200 rounded p-2">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium text-gray-900">{tool.code}</p>
                    <p className="text-xs text-gray-600">{tool.name}</p>
                  </div>
                  <div className="text-right">
                    <p className="text-sm font-bold text-yellow-600">{tool.remaining.toFixed(0)}%</p>
                    <p className="text-xs text-gray-500">남은 수명</p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {tools.length === 0 && (
        <div className="text-center py-8">
          <p className="text-gray-500">등록된 공구가 없습니다.</p>
        </div>
      )}
    </div>
  );
};

export default ToolOverview;
