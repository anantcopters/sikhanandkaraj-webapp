# QA Gate

A feature is not QA-approved until all mandatory QA areas have been reviewed and the final gate is PASS.

## Status values

- NOT STARTED
- IN QA
- FAILED
- CONDITIONAL PASS
- PASSED

## Mandatory gate

| QA Area | Result |
| --- | --- |
| Requirement QA | NOT STARTED |
| Code QA | NOT STARTED |
| UI QA | NOT STARTED |
| Validation QA | NOT STARTED |
| Database QA | NOT STARTED |
| Security QA | NOT STARTED |
| Regression QA | NOT STARTED |
| **FINAL QA GATE** | **NOT STARTED** |

## PASS criteria

The final gate may be PASSED only when:
- Requirement QA is PASS.
- Code QA is PASS.
- Validation and database integrity are acceptable.
- No unresolved CRITICAL or HIGH finding exists.
- No unresolved blocking MEDIUM finding exists unless explicitly accepted by the developer with the risk documented.
- Security QA has no unresolved release-blocking issue.
- Required regression checks pass.
- Any UI/manual checks that cannot be executed by QA are explicitly listed; they must not be silently treated as passed.

## FAIL criteria

The gate is FAILED when a mandatory requirement is missing, a blocking defect exists, authorization/data integrity is unsafe, required regression fails, or evidence contradicts expected behavior.

## CONDITIONAL PASS

Use only when implementation is acceptable but a clearly identified non-blocking/manual verification remains. The outstanding condition, owner, and risk must be recorded in the feature QA file.

## Re-QA

Re-QA updates the existing feature record. Retest resolved findings and the regression scope affected by the fix. Do not reset historical findings; retain them with their final resolution/retest status.
