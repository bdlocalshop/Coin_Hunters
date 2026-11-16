'use client';

import React, { useState } from 'react';
import { Offer } from '@/types/api';
import { useAuth } from '@/context/AuthContext';
import { api } from '@/lib/api';
import {
  X,
  Smartphone,
  Monitor,
  ExternalLink,
  ShieldCheck,
  CheckCircle,
  Coins,
  AlertCircle,
  Info,
} from 'lucide-react';

interface OfferModalProps {
  offer: Offer | null;
  onClose: () => void;
}

export const OfferModal: React.FC<OfferModalProps> = ({ offer, onClose }) => {
  const { isAuthenticated, openAuthModal } = useAuth();
  const [isStarting, setIsStarting] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  if (!offer) return null;

  const handleStartOffer = async () => {
    if (!isAuthenticated) {
      openAuthModal('register');
      return;
    }

    setIsStarting(true);
    setErrorMessage(null);

    try {
      const res = await api.offers.click(offer.id);
      if (res.redirect_url) {
        window.open(res.redirect_url, '_blank', 'noopener,noreferrer');
        onClose();
      } else {
        setErrorMessage('Failed to generate tracking redirect URL.');
      }
    } catch (err: any) {
      setErrorMessage(err.message || 'Unable to track offer. VPN/Proxy might be blocked.');
    } finally {
      setIsStarting(false);
    }
  };

  const requirementsList: string[] = Array.isArray(offer.requirements)
    ? offer.requirements
    : typeof offer.requirements === 'object' && offer.requirements !== null
    ? Object.values(offer.requirements).map((v) => String(v))
    : [
        'Install and open the app for the first time.',
        'Follow in-app instructions and complete early gameplay milestones.',
        'Allow tracking when prompted on iOS/Android.',
      ];

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-in fade-in duration-200">
      <div
        className="relative w-full max-w-lg bg-[#181313] border border-[#261D22] rounded-2xl shadow-2xl overflow-hidden"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div className="flex items-center justify-between p-5 border-b border-[#261D22]">
          <div className="flex items-center space-x-2">
            <span className="text-xs font-bold uppercase tracking-wider text-gray-400">
              {offer.provider_slug.replace('_', '.')}
            </span>
            <span className="text-gray-600">•</span>
            <span className="text-xs text-gray-400 capitalize">
              {offer.categories?.[0] || 'Offer'}
            </span>
          </div>
          <button
            onClick={onClose}
            className="p-1 rounded-lg text-gray-400 hover:text-white hover:bg-[#211919] transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Body Content */}
        <div className="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
          {/* Main Info Header */}
          <div className="flex items-start space-x-4">
            <div className="w-16 h-16 rounded-2xl bg-[#261D22] overflow-hidden shrink-0 border border-[#382A30] flex items-center justify-center">
              {offer.icon_url ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img
                  src={offer.icon_url}
                  alt={offer.title}
                  className="w-full h-full object-cover"
                />
              ) : (
                <Coins className="w-8 h-8 text-[#FACC15]" />
              )}
            </div>
            <div className="flex-1 min-w-0">
              <h2 className="text-lg font-black text-white leading-tight">
                {offer.title}
              </h2>
              <div className="flex items-center space-x-2 mt-2">
                <div className="flex items-center space-x-1 px-2.5 py-1 rounded-lg bg-[#211919] border border-[#382A30] text-xs">
                  <span className="text-gray-400">Device:</span>
                  <div className="flex items-center space-x-1 ml-1 text-white font-medium">
                    {offer.device_targets?.includes('android') && (
                      <span className="flex items-center space-x-0.5 text-emerald-400">
                        <Smartphone className="w-3 h-3" />
                        <span>Android</span>
                      </span>
                    )}
                    {offer.device_targets?.includes('ios') && (
                      <span className="flex items-center space-x-0.5 text-cyan-400">
                        <Smartphone className="w-3 h-3" />
                        <span>iOS</span>
                      </span>
                    )}
                    {offer.device_targets?.includes('desktop') && (
                      <span className="flex items-center space-x-0.5 text-gray-300">
                        <Monitor className="w-3 h-3" />
                        <span>Desktop</span>
                      </span>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Reward Box */}
          <div className="p-4 rounded-xl bg-gradient-to-r from-[#211919] via-[#1C1616] to-[#211919] border border-[#382A30] flex items-center justify-between">
            <div>
              <span className="text-xs text-gray-400 uppercase font-semibold">Total Reward</span>
              <div className="flex items-baseline space-x-2 mt-0.5">
                <span className="text-2xl font-black text-[#FACC15]">
                  +{offer.user_payout_coins.toLocaleString()} Coins
                </span>
              </div>
            </div>
            <div className="text-right">
              <span className="text-xs text-gray-500 uppercase font-mono">USD Value</span>
              <div className="text-lg font-bold text-white font-mono">
                ${(offer.user_payout_coins / 1000).toFixed(2)}
              </div>
            </div>
          </div>

          {/* Description */}
          <div>
            <h4 className="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2 flex items-center space-x-1.5">
              <Info className="w-3.5 h-3.5 text-[#FF5A36]" />
              <span>Description</span>
            </h4>
            <p className="text-sm text-gray-300 leading-relaxed bg-[#120E0E] p-3.5 rounded-xl border border-[#261D22]">
              {offer.description}
            </p>
          </div>

          {/* Instructions / Requirements Checklist */}
          <div>
            <h4 className="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2.5 flex items-center space-x-1.5">
              <CheckCircle className="w-3.5 h-3.5 text-emerald-400" />
              <span>Step-by-Step Requirements</span>
            </h4>
            <div className="space-y-2">
              {requirementsList.map((req, idx) => (
                <div
                  key={idx}
                  className="flex items-start space-x-2.5 text-xs text-gray-300 bg-[#120E0E] p-2.5 rounded-lg border border-[#261D22]"
                >
                  <span className="flex h-5 w-5 items-center justify-center rounded-full bg-[#211919] text-[#FF5A36] text-[10px] font-bold shrink-0">
                    {idx + 1}
                  </span>
                  <span className="mt-0.5">{req}</span>
                </div>
              ))}
            </div>
          </div>

          {/* Anti-Fraud Protection Notice */}
          <div className="p-3 rounded-xl bg-[#120E0E] border border-[#261D22] flex items-center space-x-2.5 text-[11px] text-gray-400">
            <ShieldCheck className="w-4 h-4 text-emerald-400 shrink-0" />
            <span>
              Real-time S2S click tracking enabled. VPNs, proxies, and emulator devices are blocked.
            </span>
          </div>

          {/* Error Message */}
          {errorMessage && (
            <div className="p-3 rounded-lg bg-red-950/40 border border-red-800/60 text-red-300 text-xs flex items-center space-x-2">
              <AlertCircle className="w-4 h-4 text-red-400 shrink-0" />
              <span>{errorMessage}</span>
            </div>
          )}
        </div>

        {/* Footer Actions */}
        <div className="p-5 border-t border-[#261D22] flex items-center justify-between bg-[#140F0F]">
          <button
            onClick={onClose}
            className="px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:text-white transition-colors"
          >
            Cancel
          </button>
          <button
            onClick={handleStartOffer}
            disabled={isStarting}
            className="px-6 py-2.5 rounded-xl bg-gradient-to-r from-[#FF5A36] to-[#F97316] hover:from-[#ea4823] hover:to-[#e0650f] text-white text-xs font-bold shadow-lg shadow-[#FF5A36]/25 transition-all flex items-center space-x-2 disabled:opacity-50"
          >
            {isStarting ? (
              <span className="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
            ) : (
              <>
                <span>Start Offer & Earn</span>
                <ExternalLink className="w-3.5 h-3.5" />
              </>
            )}
          </button>
        </div>
      </div>
    </div>
  );
};
