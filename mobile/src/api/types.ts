import type { components, operations } from './schema';

/** The JSON body of an operation's successful answer (200 or 201), from the generated API types. */
export type ResponseOf<K extends keyof operations> = operations[K] extends {
  responses: { 200: { content: { 'application/json': infer R } } };
}
  ? R
  : operations[K] extends { responses: { 201: { content: { 'application/json': infer R } } } }
    ? R
    : never;

export type Event = components['schemas']['EventResource'];
export type EventList = ResponseOf<'v1.events.index'>;
export type EventDetail = components['schemas']['EventDetailResource'];
export type AppConfig = ResponseOf<'v1.app-config'>['data'];
export type Member = components['schemas']['MemberResource'];
/** Login, activation and password reset all answer with a new token and the member. */
export type LoginResult = ResponseOf<'v1.auth.login'>;
