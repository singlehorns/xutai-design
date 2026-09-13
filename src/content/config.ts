import { defineCollection, z } from "astro:content";
import { getServices, type ServiceId } from "../data/services";
import { getSolutions, type SolutionId } from "../data/solutions";

const category = z.enum(["brand", "web", "visual", "print", "admin", "photography", "social", "motion"]);
const serviceId = z.enum(getServices().map((service) => service.id) as [ServiceId, ...ServiceId[]]);
const solutionId = z.enum(getSolutions().map((solution) => solution.id) as [SolutionId, ...SolutionId[]]);
const caseImage = z.union([z.string(), z.object({ src: z.string(), alt: z.string().optional(), caption: z.string().optional() })]);

const works = defineCollection({
  type: "content",
  schema: z.object({
    title: z.string(),
    year: z.string().optional(),
    client: z.string().optional(),
    categories: z.array(category).default([]),
    role: z.array(z.string()).default([]),
    tools: z.array(z.string()).optional(),
    summary: z.string(),
    responsibility: z.array(z.string()).default([]),
    cover: z.string().optional(),
    images: z.array(z.string()).optional(),
    externalUrl: z.string().url().optional(),
    featured: z.boolean().default(false),
    visible: z.boolean().default(true),
    order: z.number().optional(),
    imageRatio: z.string().optional(),
    status: z.enum(["complete", "pending-images", "pending-details"]).optional(),
    id: z.string().optional(),
    shortTitle: z.string().optional(),
    clientName: z.string().optional(),
    clientType: z.string().optional(),
    industry: z.string().optional(),
    projectPeriod: z.string().optional(),
    background: z.string().optional(),
    challenge: z.string().optional(),
    approach: z.string().optional(),
    roles: z.array(z.string()).optional(),
    responsibilities: z.array(z.string()).optional(),
    deliverables: z.array(z.string()).default([]),
    serviceIds: z.array(serviceId).default([]),
    solutionIds: z.array(solutionId).default([]),
    technologies: z.array(z.string()).default([]),
    coverImage: caseImage.optional(),
    gallery: z.array(caseImage).optional(),
    outcomes: z.array(z.string()).default([]),
    displayOrder: z.number().optional(),
    contentStatus: z.enum(["complete", "partial", "legacy"]).optional()
  })
});

const experience = defineCollection({
  type: "data",
  schema: z.object({
    company: z.string(),
    title: z.string(),
    period: z.string(),
    location: z.string().optional(),
    description: z.string(),
    highlights: z.array(z.string()),
    skills: z.array(z.string())
  })
});

export const collections = { works, experience };
