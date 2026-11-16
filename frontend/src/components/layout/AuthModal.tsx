'use client';

import React, { useState, useEffect } from 'react';
import { useAuth } from '@/context/AuthContext';
import { X, Lock, Mail, User as UserIcon, Gift, ArrowRight, ShieldCheck, AlertCircle } from 'lucide-react';

export const AuthModal: React.FC = () => {
  const { isAuthModalOpen, authModalTab, closeAuthModal, login, register } = useAuth();
  const [activeTab, setActiveTab] = useState<'login' | 'register'>('login');
  const [loginIdentifier, setLoginIdentifier] = useState('');
  const [loginPassword, setLoginPassword] = useState('');

  const [regUsername, setRegUsername] = useState('');
  const [regEmail, setRegEmail] = useState('');
  const [regPassword, setRegPassword] = useState('');
  const [regPasswordConfirmation, setRegPasswordConfirmation] = useState('');
  const [regReferralCode, setRegReferralCode] = useState('');

  const [isLoading, setIsLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});

  useEffect(() => {
    setActiveTab(authModalTab);
    setErrorMessage(null);
    setFieldErrors({});

    // Check URL parameters for referral code
    if (typeof window !== 'undefined') {
      const params = new URLSearchParams(window.location.search);
      const ref = params.get('ref');
      if (ref) {
        setRegReferralCode(ref);
      }
    }
  }, [authModalTab, isAuthModalOpen]);

  if (!isAuthModalOpen) return null;

  const handleLoginSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setErrorMessage(null);
    setFieldErrors({});

    try {
      await login({
        login: loginIdentifier,
        password: loginPassword,
      });
    } catch (err: any) {
      setErrorMessage(err.message || 'Login failed. Please verify your credentials.');
      if (err.errors) setFieldErrors(err.errors);
    } finally {
      setIsLoading(false);
    }
  };

  const handleRegisterSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setErrorMessage(null);
    setFieldErrors({});

    if (regPassword !== regPasswordConfirmation) {
      setErrorMessage('Passwords do not match');
      setIsLoading(false);
      return;
    }

    try {
      await register({
        username: regUsername,
        email: regEmail,
        password: regPassword,
        password_confirmation: regPasswordConfirmation,
        referral_code: regReferralCode.trim() || undefined,
      });
    } catch (err: any) {
      setErrorMessage(err.message || 'Registration failed. Please check inputs.');
      if (err.errors) setFieldErrors(err.errors);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-in fade-in duration-200">
      <div
        className="relative w-full max-w-md bg-[#181313] border border-[#261D22] rounded-2xl shadow-2xl overflow-hidden"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Top Header & Close button */}
        <div className="flex items-center justify-between px-6 pt-5 pb-4 border-b border-[#261D22]">
          <div className="flex items-center space-x-2">
            <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-[#FF5A36] to-[#F97316] text-white font-bold text-sm shadow-md shadow-[#FF5A36]/20">
              CH
            </span>
            <span className="font-extrabold tracking-wide text-white text-base">
              COIN <span className="text-[#FF5A36]">HUNTERS</span>
            </span>
          </div>
          <button
            onClick={closeAuthModal}
            className="p-1 rounded-lg text-gray-400 hover:text-white hover:bg-[#211919] transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Bonus Callout Banner */}
        <div className="bg-gradient-to-r from-[#FF5A36]/15 via-[#FACC15]/10 to-transparent border-b border-[#261D22] px-6 py-2.5 flex items-center space-x-2.5 text-xs">
          <Gift className="w-4 h-4 text-[#FACC15] shrink-0 animate-bounce" />
          <span className="text-gray-300">
            Sign up now & get <strong className="text-[#FACC15] font-semibold">250 Free Coins ($0.25)</strong> on your first offer!
          </span>
        </div>

        {/* Tab Switcher */}
        <div className="grid grid-cols-2 p-1.5 mx-6 mt-4 bg-[#120E0E] rounded-xl border border-[#261D22]">
          <button
            type="button"
            onClick={() => {
              setActiveTab('login');
              setErrorMessage(null);
            }}
            className={`py-2 text-xs font-semibold rounded-lg transition-all ${
              activeTab === 'login'
                ? 'bg-[#211919] text-white shadow-sm border border-[#382A30]'
                : 'text-gray-400 hover:text-gray-200'
            }`}
          >
            Sign In
          </button>
          <button
            type="button"
            onClick={() => {
              setActiveTab('register');
              setErrorMessage(null);
            }}
            className={`py-2 text-xs font-semibold rounded-lg transition-all ${
              activeTab === 'register'
                ? 'bg-gradient-to-r from-[#FF5A36] to-[#F97316] text-white shadow-md shadow-[#FF5A36]/20 font-bold'
                : 'text-gray-400 hover:text-gray-200'
            }`}
          >
            Create Account
          </button>
        </div>

        {/* Error Alert */}
        {errorMessage && (
          <div className="mx-6 mt-4 p-3 rounded-lg bg-red-950/40 border border-red-800/60 text-red-300 text-xs flex items-start space-x-2">
            <AlertCircle className="w-4 h-4 text-red-400 shrink-0 mt-0.5" />
            <div>
              <p className="font-medium">{errorMessage}</p>
              {fieldErrors && Object.keys(fieldErrors).length > 0 && (
                <ul className="mt-1 list-disc list-inside space-y-0.5 text-red-400 text-[11px]">
                  {Object.entries(fieldErrors).map(([key, errors]) => (
                    <li key={key}>{errors[0]}</li>
                  ))}
                </ul>
              )}
            </div>
          </div>
        )}

        {/* Forms Body */}
        <div className="p-6">
          {activeTab === 'login' ? (
            <form onSubmit={handleLoginSubmit} className="space-y-4">
              <div>
                <label className="block text-xs font-medium text-gray-300 mb-1.5">
                  Username or Email
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                    <UserIcon className="w-4 h-4" />
                  </div>
                  <input
                    type="text"
                    required
                    value={loginIdentifier}
                    onChange={(e) => setLoginIdentifier(e.target.value)}
                    placeholder="player1 or player1@example.com"
                    className="w-full pl-9 pr-3 py-2.5 bg-[#120E0E] border border-[#261D22] rounded-xl text-sm text-white placeholder-gray-500 focus:outline-none focus:border-[#FF5A36] focus:ring-1 focus:ring-[#FF5A36] transition-colors"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-300 mb-1.5">
                  Password
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                    <Lock className="w-4 h-4" />
                  </div>
                  <input
                    type="password"
                    required
                    value={loginPassword}
                    onChange={(e) => setLoginPassword(e.target.value)}
                    placeholder="••••••••"
                    className="w-full pl-9 pr-3 py-2.5 bg-[#120E0E] border border-[#261D22] rounded-xl text-sm text-white placeholder-gray-500 focus:outline-none focus:border-[#FF5A36] focus:ring-1 focus:ring-[#FF5A36] transition-colors"
                  />
                </div>
              </div>

              <button
                type="submit"
                disabled={isLoading}
                className="w-full mt-2 py-3 px-4 rounded-xl bg-gradient-to-r from-[#FF5A36] to-[#F97316] hover:from-[#ea4823] hover:to-[#e0650f] text-white text-sm font-bold shadow-lg shadow-[#FF5A36]/25 transition-all transform active:scale-[0.99] disabled:opacity-50 flex items-center justify-center space-x-2"
              >
                {isLoading ? (
                  <span className="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                ) : (
                  <>
                    <span>Sign In</span>
                    <ArrowRight className="w-4 h-4" />
                  </>
                )}
              </button>
            </form>
          ) : (
            <form onSubmit={handleRegisterSubmit} className="space-y-3.5">
              <div>
                <label className="block text-xs font-medium text-gray-300 mb-1">
                  Username
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                    <UserIcon className="w-4 h-4" />
                  </div>
                  <input
                    type="text"
                    required
                    minLength={3}
                    maxLength={30}
                    value={regUsername}
                    onChange={(e) => setRegUsername(e.target.value)}
                    placeholder="coinmaster99"
                    className="w-full pl-9 pr-3 py-2 bg-[#120E0E] border border-[#261D22] rounded-xl text-sm text-white placeholder-gray-500 focus:outline-none focus:border-[#FF5A36] focus:ring-1 focus:ring-[#FF5A36] transition-colors"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-300 mb-1">
                  Email Address
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">
                    <Mail className="w-4 h-4" />
                  </div>
                  <input
                    type="email"
                    required
                    value={regEmail}
                    onChange={(e) => setRegEmail(e.target.value)}
                    placeholder="gamer@example.com"
                    className="w-full pl-9 pr-3 py-2 bg-[#120E0E] border border-[#261D22] rounded-xl text-sm text-white placeholder-gray-500 focus:outline-none focus:border-[#FF5A36] focus:ring-1 focus:ring-[#FF5A36] transition-colors"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-2.5">
                <div>
                  <label className="block text-xs font-medium text-gray-300 mb-1">
                    Password
                  </label>
                  <input
                    type="password"
                    required
                    minLength={8}
                    value={regPassword}
                    onChange={(e) => setRegPassword(e.target.value)}
                    placeholder="••••••••"
                    className="w-full px-3 py-2 bg-[#120E0E] border border-[#261D22] rounded-xl text-sm text-white placeholder-gray-500 focus:outline-none focus:border-[#FF5A36] focus:ring-1 focus:ring-[#FF5A36] transition-colors"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-gray-300 mb-1">
                    Confirm
                  </label>
                  <input
                    type="password"
                    required
                    minLength={8}
                    value={regPasswordConfirmation}
                    onChange={(e) => setRegPasswordConfirmation(e.target.value)}
                    placeholder="••••••••"
                    className="w-full px-3 py-2 bg-[#120E0E] border border-[#261D22] rounded-xl text-sm text-white placeholder-gray-500 focus:outline-none focus:border-[#FF5A36] focus:ring-1 focus:ring-[#FF5A36] transition-colors"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-medium text-gray-400 mb-1">
                  Referral Code (Optional)
                </label>
                <input
                  type="text"
                  value={regReferralCode}
                  onChange={(e) => setRegReferralCode(e.target.value)}
                  placeholder="e.g. FRIEND2026"
                  className="w-full px-3 py-2 bg-[#120E0E] border border-[#261D22] rounded-xl text-xs text-white placeholder-gray-600 focus:outline-none focus:border-[#FF5A36] transition-colors uppercase tracking-wider"
                />
              </div>

              <button
                type="submit"
                disabled={isLoading}
                className="w-full mt-2 py-3 px-4 rounded-xl bg-gradient-to-r from-[#FF5A36] to-[#F97316] hover:from-[#ea4823] hover:to-[#e0650f] text-white text-sm font-bold shadow-lg shadow-[#FF5A36]/25 transition-all transform active:scale-[0.99] disabled:opacity-50 flex items-center justify-center space-x-2"
              >
                {isLoading ? (
                  <span className="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                ) : (
                  <>
                    <span>Create Free Account</span>
                    <Gift className="w-4 h-4 text-[#FACC15]" />
                  </>
                )}
              </button>
            </form>
          )}

          {/* Security & Anti-Fraud Footer Notice */}
          <div className="mt-4 pt-3 border-t border-[#261D22] flex items-center justify-between text-[11px] text-gray-500">
            <span className="flex items-center space-x-1">
              <ShieldCheck className="w-3.5 h-3.5 text-emerald-500" />
              <span>Anti-Fraud & Proxy Guard</span>
            </span>
            <span>1,000 Coins = $1.00 USD</span>
          </div>
        </div>
      </div>
    </div>
  );
};
