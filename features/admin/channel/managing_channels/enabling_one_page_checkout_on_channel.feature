@one_page_checkout_managing_channels
Feature: Enabling one page checkout on a channel
    In order to offer a single-page checkout to my customers
    As an Administrator
    I want to be able to toggle one page checkout on a channel

    Background:
        Given the store operates on a channel named "Web Channel"
        And I am logged in as an administrator

    @ui @no-api
    Scenario: Enabling one page checkout on a channel
        When I want to modify this channel
        And I enable one page checkout
        And I save my changes
        Then I should be notified that it has been successfully edited
        And one page checkout should be enabled for this channel

    @ui @no-api
    Scenario: Disabling one page checkout on a channel
        Given one page checkout is enabled for this channel
        When I want to modify this channel
        And I disable one page checkout
        And I save my changes
        Then I should be notified that it has been successfully edited
        And one page checkout should be disabled for this channel
