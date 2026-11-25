/**
 * @internal
 */
export const hashPassword = (value: string): string => value;

/**
 * @internal
 */
export class PasswordHasher {
  private readonly salt = 'salt';

  /**
   * @internal @acme/email
   */
  public rehash(value: string): string {
    return `${value}${this.salt}`;
  }

  public verify(value: string): string {
    return `${value}${this.salt}`;
  }
}

export interface HashOptions {
  /**
   * @internal
   */
  secret: string;

  label: string;
}

/**
 * @internal
 */
export type HashToken = string;
export const describeHash = (value: string): string => value;

/**
 * @internal
 */
export function hashLegacy(value: string): string {
  return value;
}
