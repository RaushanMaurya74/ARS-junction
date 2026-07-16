# ARS Junction Workspace Rules

## Important Development Rules

1. **Never break existing functionality**: Ensure all existing features (e.g. dynamic pricing, Leaflet.js tracking, base64 uploads, session cookie encryption) remain fully active and regression-free.
2. **Never remove existing APIs**: Maintain complete backward compatibility for all frontend and background polling endpoints.
3. **Never delete database tables**: Keep database schema intact; only apply incremental, non-destructive migrations.
4. **Always build modular components**: Use isolated helper functions, templates, and libraries.
5. **Keep Restaurant completely isolated from Admin**: The admin panel and restaurant management modules must reside in separate routing controls and logical domains.
6. **Each Restaurant only sees its own data**: Query scopes for restaurants must be strictly limited to their own ID.
7. **Every Restaurant manages only its own Delivery Boys**: Delivery boy registry and assignment scopes are restaurant-bound.
8. **Every Delivery Boy can only access orders assigned by their own Restaurant**: Restrict access to order records in the delivery boy dashboard based on restaurant boundaries.
9. **Maintain production-ready coding standards**: Write strict, clean, SQL-injection-safe (prepared statement PDO), and Vercel-compatible PHP code.
10. **Document every new feature before implementation**: Update development plans and walkthroughs as updates are deployed.
