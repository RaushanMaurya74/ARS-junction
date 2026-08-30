-- Fix for: Public / Signed-In Users Can Execute SECURITY DEFINER Function (rls_auto_enable)
-- This revokes EXECUTE permission from public roles for the rls_auto_enable function
-- which prevents malicious privilege escalation attacks.

REVOKE EXECUTE ON FUNCTION public.rls_auto_enable() FROM public, anon, authenticated;
