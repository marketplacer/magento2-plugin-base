const { generateCommitlintConfig } = require("@marketplacer/commitlint-config");

/** Permissible scopes for commits which present tangible change to end users.
 *  New scopes may be added as their own commit, and should be discussed to
 *  confirm it is a reasonable addition. All scopes should have a description
 *  supplied.
 *
 *  Try to keep this list in alphabetical order.
 */
const externalFacingScopes = [];

/** Collection of tuples specifying commit type, and permitted scopes for the
 *  commit type */
const typesRequiringScopes = [];

module.exports = generateCommitlintConfig({ typesRequiringScopes });
