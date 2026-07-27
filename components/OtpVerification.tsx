import React, { useState, useRef, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Check, Loader2, AlertCircle, RefreshCw, ShieldCheck, ArrowRight } from 'lucide-react';

interface OtpVerificationProps {
  phoneNumber?: string;
  onVerify?: (otp: string) => Promise<boolean> | boolean;
  onResend?: () => void;
  length?: number;
}

export const OtpVerification: React.FC<OtpVerificationProps> = ({
  phoneNumber = "+1 (555) 019-2834",
  onVerify,
  onResend,
  length = 6,
}) => {
  const [otp, setOtp] = useState<string[]>(Array(length).fill(''));
  const [activeInputIndex, setActiveInputIndex] = useState<number>(0);
  const [status, setStatus] = useState<'idle' | 'loading' | 'success' | 'error'>('idle');
  const [errorMessage, setErrorMessage] = useState<string>('');
  const [resendTimer, setResendTimer] = useState<number>(30);
  const [canResend, setCanResend] = useState<boolean>(false);
  const inputRefs = useRef<(HTMLInputElement | null)[]>([]);

  // Timer Countdown Logic
  useEffect(() => {
    let interval: NodeJS.Timeout;
    if (resendTimer > 0 && !canResend) {
      interval = setInterval(() => {
        setResendTimer((prev) => prev - 1);
      }, 1000);
    } else if (resendTimer === 0) {
      setCanResend(true);
    }
    return () => clearInterval(interval);
  }, [resendTimer, canResend]);

  // Auto Focus First Input on Mount
  useEffect(() => {
    inputRefs.current[0]?.focus();
  }, []);

  // Handle Input Changes
  const handleChange = (e: React.ChangeEvent<HTMLInputElement>, index: number) => {
    const value = e.target.value;
    if (isNaN(Number(value))) return;

    // Reset error state on typing
    if (status === 'error') {
      setStatus('idle');
      setErrorMessage('');
    }

    const newOtp = [...otp];
    // Take last entered character if length > 1 (overwrite case)
    const char = value.substring(value.length - 1);
    newOtp[index] = char;
    setOtp(newOtp);

    // Auto advance to next box
    if (char && index < length - 1) {
      setActiveInputIndex(index + 1);
      inputRefs.current[index + 1]?.focus();
    }

    // Check if fully filled to auto-verify
    const fullOtp = newOtp.join('');
    if (fullOtp.length === length && !newOtp.includes('')) {
      handleVerification(fullOtp);
    }
  };

  // Handle Key Down (Backspace & Navigation)
  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>, index: number) => {
    if (e.key === 'Backspace') {
      if (!otp[index] && index > 0) {
        // Move back and clear
        setActiveInputIndex(index - 1);
        inputRefs.current[index - 1]?.focus();
      }
    } else if (e.key === 'ArrowLeft' && index > 0) {
      setActiveInputIndex(index - 1);
      inputRefs.current[index - 1]?.focus();
    } else if (e.key === 'ArrowRight' && index < length - 1) {
      setActiveInputIndex(index + 1);
      inputRefs.current[index + 1]?.focus();
    }
  };

  // Handle Paste Event
  const handlePaste = (e: React.ClipboardEvent<HTMLInputElement>) => {
    e.preventDefault();
    const pastedData = e.clipboardData.getData('text').trim();
    if (!/^\d+$/.test(pastedData)) return; // Only numbers allowed

    const digits = pastedData.slice(0, length).split('');
    const newOtp = [...otp];

    digits.forEach((digit, i) => {
      newOtp[i] = digit;
    });

    setOtp(newOtp);

    // Focus last filled or next empty box
    const focusIndex = Math.min(digits.length, length - 1);
    setActiveInputIndex(focusIndex);
    inputRefs.current[focusIndex]?.focus();

    // Auto trigger if full code pasted
    if (digits.length === length) {
      handleVerification(digits.join(''));
    }
  };

  // Submit Handler
  const handleVerification = async (codeToVerify?: string) => {
    const finalOtp = codeToVerify || otp.join('');
    if (finalOtp.length !== length) {
      setStatus('error');
      setErrorMessage('Please enter all 6 digits.');
      triggerHaptic();
      return;
    }

    setStatus('loading');
    setErrorMessage('');

    try {
      let isSuccess = false;
      if (onVerify) {
        isSuccess = await onVerify(finalOtp);
      } else {
        // Simulated Default Verification: "123456" is valid
        await new Promise((res) => setTimeout(res, 1200));
        isSuccess = finalOtp === '123456';
      }

      if (isSuccess) {
        setStatus('success');
      } else {
        setStatus('error');
        setErrorMessage('Invalid verification code. Please try again.');
        triggerHaptic();
      }
    } catch (err) {
      setStatus('error');
      setErrorMessage('Verification service error. Try again.');
      triggerHaptic();
    }
  };

  // Mobile Haptic Vibration Support
  const triggerHaptic = () => {
    if (typeof window !== 'undefined' && window.navigator && window.navigator.vibrate) {
      window.navigator.vibrate([100, 50, 100]);
    }
  };

  // Handle Resend Trigger
  const handleResendCode = () => {
    if (!canResend) return;
    setOtp(Array(length).fill(''));
    setStatus('idle');
    setErrorMessage('');
    setResendTimer(30);
    setCanResend(false);
    setActiveInputIndex(0);
    inputRefs.current[0]?.focus();
    if (onResend) onResend();
  };

  // Card Shake Animation Variants for Error State
  const cardShakeVariants = {
    idle: { x: 0 },
    error: {
      x: [-12, 12, -8, 8, -4, 4, 0],
      transition: { duration: 0.5, ease: "easeInOut" },
    },
  };

  return (
    <div className="relative min-h-screen w-full bg-[#0B0B0B] text-white flex items-center justify-center p-4 font-sans overflow-hidden antialiased select-none">
      {/* Background Lighting & Glow Effects */}
      <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-gradient-to-tr from-orange-600/25 via-amber-500/15 to-transparent rounded-full blur-[120px] pointer-events-none animate-pulse" />
      <div className="absolute -top-32 -left-32 w-96 h-96 bg-orange-500/10 rounded-full blur-[100px] pointer-events-none" />
      <div className="absolute -bottom-32 -right-32 w-96 h-96 bg-amber-500/10 rounded-full blur-[100px] pointer-events-none" />

      {/* Main Glassmorphism Card */}
      <motion.div
        variants={cardShakeVariants}
        animate={status === 'error' ? 'error' : 'idle'}
        initial={{ opacity: 0, scale: 0.94, y: 20 }}
        animate={{ opacity: 1, scale: 1, y: 0 }}
        transition={{ duration: 0.6, ease: [0.16, 1, 0.3, 1] }}
        className="relative w-full max-w-[420px] bg-[#141414]/75 backdrop-blur-[25px] border border-white/[0.08] rounded-[28px] p-8 shadow-[0_20px_50px_rgba(0,0,0,0.8)] shadow-orange-950/10 overflow-hidden"
      >
        {/* Top Shimmer Border Line */}
        <div className="absolute inset-x-0 top-0 h-[1px] bg-gradient-to-r from-transparent via-orange-500/40 to-transparent" />

        <AnimatePresence mode="wait">
          {status === 'success' ? (
            /* SUCCESS STATE VIEW */
            <motion.div
              key="success-view"
              initial={{ opacity: 0, scale: 0.85 }}
              animate={{ opacity: 1, scale: 1 }}
              exit={{ opacity: 0, scale: 0.85 }}
              transition={{ duration: 0.5, ease: "easeOut" }}
              className="flex flex-col items-center text-center py-6"
            >
              {/* Check Icon with Outer Pulse Ring */}
              <div className="relative mb-6 flex items-center justify-center">
                <motion.div
                  initial={{ scale: 0 }}
                  animate={{ scale: [0, 1.25, 1] }}
                  transition={{ duration: 0.6, times: [0, 0.7, 1] }}
                  className="w-20 h-20 rounded-full bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-400 shadow-[0_0_30px_rgba(16,185,129,0.3)]"
                >
                  <Check className="w-10 h-10 stroke-[2.5]" />
                </motion.div>
                <div className="absolute w-24 h-24 rounded-full border border-emerald-500/20 animate-ping opacity-40" />
              </div>

              <motion.h2
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.2 }}
                className="text-2xl font-bold text-white tracking-tight"
              >
                Verification Successful
              </motion.h2>

              <motion.p
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.3 }}
                className="text-sm text-neutral-400 mt-2 max-w-[280px]"
              >
                Your phone number has been authenticated. Redirecting to dashboard...
              </motion.p>

              <motion.div
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.4 }}
                className="mt-8 flex items-center gap-2 text-xs font-semibold text-emerald-400 bg-emerald-950/40 border border-emerald-800/40 px-4 py-2 rounded-full"
              >
                <ShieldCheck className="w-4 h-4" />
                <span>Security Token Granted</span>
              </motion.div>
            </motion.div>
          ) : (
            /* OTP VERIFICATION VIEW */
            <motion.div
              key="otp-view"
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
            >
              {/* Header */}
              <div className="text-center mb-8">
                <div className="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-orange-500/10 border border-orange-500/20 text-orange-400 mb-4 shadow-[0_0_15px_rgba(249,115,22,0.15)]">
                  <ShieldCheck className="w-6 h-6" />
                </div>
                <h1 className="text-2xl font-bold text-white tracking-tight">
                  Let's verify your number
                </h1>
                <p className="text-sm text-neutral-400 mt-2 leading-relaxed">
                  We've sent a 6-digit verification code to{' '}
                  <span className="text-neutral-200 font-medium">{phoneNumber}</span>. It will auto verify once entered.
                </p>
              </div>

              {/* OTP Input Grid */}
              <div className="flex justify-between items-center gap-2 mb-6" onPaste={handlePaste}>
                {otp.map((digit, index) => {
                  const isFocused = activeInputIndex === index;
                  const isFilled = digit !== '';

                  return (
                    <motion.div
                      key={index}
                      whileHover={{ scale: 1.03 }}
                      whileTap={{ scale: 0.97 }}
                      animate={isFilled ? { scale: [1, 1.08, 1] } : { scale: 1 }}
                      transition={{ duration: 0.15 }}
                      className="relative flex-1"
                    >
                      <input
                        ref={(el) => (inputRefs.current[index] = el)}
                        type="text"
                        inputMode="numeric"
                        autoComplete="one-time-code"
                        maxLength={1}
                        value={digit}
                        onFocus={() => setActiveInputIndex(index)}
                        onChange={(e) => handleChange(e, index)}
                        onKeyDown={(e) => handleKeyDown(e, index)}
                        className={`w-full h-14 sm:h-[60px] text-center text-2xl font-bold rounded-[18px] bg-[#0F0F0F]/80 text-white outline-none transition-all duration-200 border ${
                          status === 'error'
                            ? 'border-red-500/80 bg-red-950/10 text-red-400 shadow-[0_0_15px_rgba(239,68,68,0.3)]'
                            : isFocused
                            ? 'border-orange-500 bg-[#161616] shadow-[0_0_20px_rgba(249,115,22,0.35)] ring-1 ring-orange-500/50'
                            : isFilled
                            ? 'border-neutral-700/80 bg-[#181818] text-white'
                            : 'border-neutral-800/80 hover:border-neutral-700'
                        }`}
                        aria-label={`OTP Digit ${index + 1}`}
                      />
                      {/* Active Input Indicator Dot */}
                      {isFocused && !isFilled && (
                        <span className="absolute bottom-2 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-orange-500 rounded-full animate-ping pointer-events-none" />
                      )}
                    </motion.div>
                  );
                })}
              </div>

              {/* Error Message */}
              <AnimatePresence>
                {status === 'error' && (
                  <motion.div
                    initial={{ opacity: 0, y: -6 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, y: -6 }}
                    className="flex items-center justify-center gap-2 text-xs font-medium text-red-400 mb-6 bg-red-950/20 border border-red-900/30 py-2.5 px-3 rounded-xl"
                  >
                    <AlertCircle className="w-4 h-4 shrink-0" />
                    <span>{errorMessage}</span>
                  </motion.div>
                )}
              </AnimatePresence>

              {/* Main Action / Verify Button */}
              <motion.button
                whileHover={{ scale: status === 'loading' ? 1 : 1.01 }}
                whileTap={{ scale: status === 'loading' ? 1 : 0.98 }}
                onClick={() => handleVerification()}
                disabled={status === 'loading'}
                className={`w-full h-[52px] rounded-[16px] font-semibold text-base transition-all duration-300 flex items-center justify-center gap-2 shadow-lg ${
                  status === 'loading'
                    ? 'bg-orange-600/60 cursor-not-allowed text-white/70'
                    : 'bg-gradient-to-r from-orange-500 via-orange-600 to-amber-600 hover:from-orange-400 hover:to-amber-500 text-white shadow-orange-600/30 hover:shadow-[0_0_25px_rgba(249,115,22,0.45)]'
                }`}
              >
                {status === 'loading' ? (
                  <>
                    <Loader2 className="w-5 h-5 animate-spin" />
                    <span>Verifying Code...</span>
                  </>
                ) : (
                  <>
                    <span>Verify Code</span>
                    <ArrowRight className="w-5 h-5" />
                  </>
                )}
              </motion.button>

              {/* Bottom Resend Code Section */}
              <div className="mt-6 text-center text-sm text-neutral-400">
                <span>Didn't receive the code? </span>
                <button
                  onClick={handleResendCode}
                  disabled={!canResend || status === 'loading'}
                  className={`font-semibold transition-all duration-200 inline-flex items-center gap-1 ${
                    canResend
                      ? 'text-orange-400 hover:text-orange-300 hover:underline cursor-pointer'
                      : 'text-neutral-600 cursor-not-allowed'
                  }`}
                >
                  {canResend ? (
                    <>
                      <RefreshCw className="w-3.5 h-3.5" />
                      <span>Resend</span>
                    </>
                  ) : (
                    <span>Resend in {resendTimer}s</span>
                  )}
                </button>
              </div>
            </motion.div>
          )}
        </AnimatePresence>
      </motion.div>
    </div>
  );
};

export default OtpVerification;
