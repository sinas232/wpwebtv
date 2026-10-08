import type { PriceDisplay } from "@/lib/site";

export type Complexity = "low" | "medium" | "high";

export type FaqItem = { q: string; a: string };

export type Service = {
  slug: string;
  title: string;
  summary: string;
  body: string[];
  complexity: Complexity;
  price: PriceDisplay;
  relatedProblems: string[];
  faq: FaqItem[];
};

export type Brand = {
  slug: string;
  name: string;
  latin: string;
  intro: string;
  commonIssues: string[];
  relatedProblems: string[];
};

export type Problem = {
  slug: string;
  title: string;
  summary: string;
  signs: string[];
  causes: string[];
  safeSteps: string[];
  callTechnician: string[];
  complexity: Complexity;
  price: PriceDisplay;
  relatedServices: string[];
  faq: FaqItem[];
};

export type City = {
  slug: string;
  name: string;
  /** Coverage is shown only when `coverage` is "confirmed". */
  coverage: "confirmed" | "pending";
  intro: string;
  visitNotes: string[];
};

export type BlogPost = {
  slug: string;
  title: string;
  excerpt: string;
  category: string;
  author: string;
  publishedAt: string;
  body: string[];
  relatedProblems: string[];
};

export type LeadStatus =
  | "received"
  | "reviewing"
  | "technician_assigned"
  | "visit_scheduled"
  | "dispatched"
  | "in_repair"
  | "completed";
