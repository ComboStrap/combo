#!/bin/bash
# https://docs.github.com/en/actions/learn-github-actions/contexts#example-usage-of-the-github-context
# https://docs.github.com/en/actions/learn-github-actions/variables#using-the-vars-context-to-access-configuration-variable-values
# https://docs.github.com/en/actions/learn-github-actions/variables#default-environment-variables
if [ "${GITHUB_EVENT_NAME}" == 'pull_request' ];
then
    # The head ref or source branch of the pull request in a workflow run.
    # This property is only set when the event that triggers a workflow run is either pull_request or pull_request_target.
    # For example, feature-branch-1
    BUILD_BRANCH=${GITHUB_HEAD_REF};
    # The name of the base ref or target branch of the pull request in a workflow run.
    # This is only set when the event that triggers a workflow run is either pull_request or pull_request_target.
    # For example, main
    BUILD_TARGET_BRANCH=${GITHUB_BASE_REF};
    echo -e "\nPull request from the branch (${BUILD_BRANCH}) to the branch (${BUILD_TARGET_BRANCH})";
else
    # The short ref name of the branch or tag that triggered the workflow run.
    # This value matches the branch or tag name shown on GitHub.
    # For example, feature-branch-1 or 57/merge for a pull request
    BUILD_BRANCH=${GITHUB_REF_NAME};
fi;

echo -e "\nGet boot.sh from from the branch (${BUILD_BRANCH})";
curl -H "Authorization: token ${TOKEN}" -o "boot.sh" "https://raw.githubusercontent.com/ComboStrap/combo_test/${BUILD_BRANCH}/resources/script/ci/boot.sh"
echo -e "\nRun boot.sh";
chmod +x boot.sh
source boot.sh
