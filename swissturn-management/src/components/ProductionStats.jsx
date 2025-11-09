import React from 'react';
import { useApp } from '../contexts/AppContext';
import { TrendingUp, Package, XCircle, CheckCircle2 } from 'lucide-react';

const ProductionStats = () => {
  const { productionData, machines } = useApp();

  // Calculate production statistics
  const totalProduced = productionData.reduce((sum, record) => sum + (record.produced || 0), 0);
  const totalDefects = productionData.reduce((sum, record) => sum + (record.defects || 0), 0);
  const qualityRate = totalProduced > 0 ? ((totalProduced - totalDefects) / totalProduced * 100) : 100;

  // Calculate total runtime
  const totalRuntime = machines.reduce((sum, m) => sum + (m.runtime || 0), 0);
  const totalDowntime = machines.reduce((sum, m) => sum + (m.downtime || 0), 0);

  const utilizationRate = totalRuntime + totalDowntime > 0
    ? (totalRuntime / (totalRuntime + totalDowntime) * 100)
    : 0;

  return (
    <div className="bg-white rounded-lg shadow p-6">
      <h2 className="text-lg font-semibold text-gray-900 mb-4">생산 통계</h2>

      {/* Key Metrics */}
      <div className="grid grid-cols-2 gap-4 mb-4">
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div className="flex items-center gap-2 mb-2">
            <Package className="h-5 w-5 text-blue-600" />
            <p className="text-xs font-medium text-blue-900">총 생산량</p>
          </div>
          <p className="text-2xl font-bold text-blue-900">{totalProduced.toLocaleString()}</p>
          <p className="text-xs text-blue-700 mt-1">개</p>
        </div>

        <div className="bg-green-50 border border-green-200 rounded-lg p-4">
          <div className="flex items-center gap-2 mb-2">
            <CheckCircle2 className="h-5 w-5 text-green-600" />
            <p className="text-xs font-medium text-green-900">품질률</p>
          </div>
          <p className="text-2xl font-bold text-green-900">{qualityRate.toFixed(1)}%</p>
          <p className="text-xs text-green-700 mt-1">양품 비율</p>
        </div>
      </div>

      {/* Runtime Statistics */}
      <div className="mb-4">
        <div className="flex items-center justify-between mb-2">
          <p className="text-sm font-medium text-gray-700">장비 가동률</p>
          <p className="text-sm font-bold text-gray-900">{utilizationRate.toFixed(1)}%</p>
        </div>
        <div className="w-full bg-gray-200 rounded-full h-3">
          <div
            className="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all"
            style={{ width: `${Math.min(100, utilizationRate)}%` }}
          />
        </div>
        <div className="flex items-center justify-between mt-1">
          <p className="text-xs text-gray-500">가동: {totalRuntime.toFixed(1)}h</p>
          <p className="text-xs text-gray-500">정지: {totalDowntime.toFixed(1)}h</p>
        </div>
      </div>

      {/* Defects */}
      <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <div className="flex items-center gap-2 mb-2">
          <XCircle className="h-5 w-5 text-red-600" />
          <p className="text-xs font-medium text-gray-900">불량 현황</p>
        </div>
        <div className="flex items-baseline gap-2">
          <p className="text-2xl font-bold text-red-600">{totalDefects.toLocaleString()}</p>
          <p className="text-xs text-gray-600">개</p>
        </div>
        {totalProduced > 0 && (
          <p className="text-xs text-gray-500 mt-1">
            불량률: {((totalDefects / totalProduced) * 100).toFixed(2)}%
          </p>
        )}
      </div>

      {/* Production Trend Placeholder */}
      <div className="mt-4 pt-4 border-t border-gray-200">
        <div className="flex items-center gap-2 mb-3">
          <TrendingUp className="h-4 w-4 text-gray-600" />
          <p className="text-sm font-medium text-gray-700">일일 생산 추이</p>
        </div>
        <div className="h-24 bg-gray-50 rounded-lg flex items-center justify-center">
          <p className="text-xs text-gray-500">차트 데이터 준비 중</p>
        </div>
      </div>
    </div>
  );
};

export default ProductionStats;
